<?php

declare(strict_types=1);

class RequestController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly RequestService $requests = new RequestService(),
        private readonly RoomService $rooms = new RoomService(),
        private readonly ResourceService $resources = new ResourceService()
    ) {
        parent::__construct($request);
    }

    protected string $table = 'exam_requests';
    protected string $viewPath = 'requests';
    protected string $entityName = 'request';
    protected array $fillable = [
        'requested_by', 'event_id', 'course_name', 'students_count', 'preferred_room_id',
        'preferred_delivery_mode', 'preferred_starts_at', 'preferred_ends_at',
        'online_platform', 'online_url', 'online_access_key', 'online_access_password',
        'online_host_user_id', 'online_moderator_name', 'online_access_instructions',
        'required_resources', 'notes', 'status', 'reviewed_by', 'reviewed_at',
    ];
    protected array $required = ['course_name', 'students_count'];
    protected array $integerFields = ['requested_by', 'event_id', 'students_count', 'preferred_room_id', 'reviewed_by', 'online_host_user_id'];
    protected array $jsonFields = ['required_resources'];
    protected array $listFilters = ['status', 'requested_by', 'preferred_delivery_mode'];
    protected string $defaultOrder = 'created_at DESC';

    public function store(): mixed
    {
        $this->authorizeCreate();
        $data = $this->payload();
        $data['requested_by'] ??= $this->currentUserId();
        $result = $this->requests->create($this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create', $this->formData());
        }

        /** @var ExamRequest|null $request */
        $request = $result['request'] ?? null;

        return $this->storedResponse($request?->toArray() ?? []);
    }

    public function index(): mixed
    {
        $user = $this->currentUser();

        if ($this->isTeacher($user)) {
            return $this->respond($this->viewPath . '/index', [
                $this->collectionKey() => $this->rows(
                    null,
                    []
                ),
                'filters' => $this->filters(),
            ]);
        }

        return parent::index();
    }

    public function details(int|string $id): mixed
    {
        $request = $this->findOrFail($id);
        $this->authorizeRequestAccess($request);

        return $this->respond($this->viewPath . '/details', [
            $this->entityName => $request,
        ]);
    }

    public function edit(int|string $id): string
    {
        $request = $this->findOrFail($id);
        $this->authorizeRequestAccess($request);

        return $this->render($this->viewPath . '/edit', array_merge($this->formData(), [
            $this->entityName => $request,
        ]));
    }

    public function update(int|string $id): mixed
    {
        $request = $this->findOrFail($id);
        $this->authorizeRequestAccess($request);
        $data = $this->payload();
        $result = $this->requests->update($id, $this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/edit', array_merge($this->formData(), [
                $this->entityName => array_merge($request, $data),
            ]));
        }

        /** @var ExamRequest|null $updated */
        $updated = $result['request'] ?? null;

        return $this->updatedResponse($updated?->toArray() ?? $this->findOrFail($id));
    }

    public function delete(int|string $id): mixed
    {
        $request = $this->findOrFail($id);
        $this->authorizeRequestAccess($request);
        $deleted = $this->requests->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('Request not found.');
        }

        return $this->deletedResponse();
    }

    public function review(int|string $id): mixed
    {
        $this->authorizeReview();
        $request = $this->findOrFail($id);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->respond('requests/review', ['request' => $request]);
        }

        $data = $this->payload();
        $status = (string) ($data['status'] ?? '');

        if (!in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            return $this->validationResponse(['status' => 'Invalid review status.'], $data, 'requests/review', [
                'request' => $request,
            ]);
        }

        $result = $this->requests->review(
            $id,
            $status,
            $this->currentUserId(),
            isset($data['notes']) ? (string) $data['notes'] : (string) ($request['notes'] ?? '')
        );

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? ['status' => 'Unable to review request.']), $data, 'requests/review', [
                'request' => $request,
            ]);
        }

        /** @var ExamRequest|null $updated */
        $updated = $result['request'] ?? null;

        if ($updated === null) {
            throw new RuntimeException('Reviewed request could not be loaded.');
        }

        return $this->updatedResponse($updated->toArray());
    }

    public function schedule(int|string $id): mixed
    {
        $this->authorizeReview();

        $result = $this->requests->createEventFromRequest($id, $this->currentUserId());
        if (!($result['success'] ?? false)) {
            $request = $this->findOrFail($id);

            return $this->validationResponse((array) ($result['errors'] ?? ['request' => 'Unable to schedule request.']), [], 'requests/review', [
                'request' => $request,
            ]);
        }

        /** @var ExamRequest|null $updated */
        $updated = $result['request'] ?? null;

        if ($updated === null) {
            throw new RuntimeException('Scheduled request could not be loaded.');
        }

        if ($this->wantsJson()) {
            /** @var Event|null $event */
            $event = $result['event'] ?? null;

            return [
                'request' => $updated->toArray(),
                'event' => $event?->toArray(),
                'message' => 'Request scheduled successfully.',
            ];
        }

        $this->redirect($this->indexUrl());
        return null;
    }

    protected function formData(): array
    {
        return [
            'rooms' => array_map(
                static fn (Room $room): array => $room->toArray(),
                $this->rooms->listing()
            ),
            'resources' => array_map(
                static fn (Resource $resource): array => $resource->toArray(),
                $this->resources->listing()
            ),
        ];
    }

    private function authorizeCreate(): void
    {
        $user = $this->currentUser();

        if ($this->isManager($user) || $this->isTeacher($user)) {
            return;
        }

        $this->denyAccess(403, 'You do not have permission to create requests.');
    }

    private function authorizeReview(): void
    {
        if ($this->isManager($this->currentUser())) {
            return;
        }

        $this->denyAccess(403, 'Only schedule managers and administrators can review requests.');
    }

    private function authorizeRequestAccess(array $request): void
    {
        $user = $this->currentUser();

        if ($this->isManager($user)) {
            return;
        }

        if ($this->isTeacher($user) && (int) ($request['requested_by'] ?? 0) === (int) $this->currentUserId()) {
            return;
        }

        $this->denyAccess(403, 'You do not have permission to access this request.');
    }

    private function currentUser(): ?array
    {
        return AuthMiddleware::resolveUser();
    }

    private function isManager(?array $user): bool
    {
        return is_array($user) && AuthMiddleware::userHasRole($user, [ROLE_SYSTEM_ADMIN, ROLE_SCHEDULE_MANAGER, 'system_admin', 'schedule_manager']);
    }

    private function isTeacher(?array $user): bool
    {
        return is_array($user) && AuthMiddleware::userHasRole($user, [ROLE_TEACHER, 'teacher']);
    }

    private function denyAccess(int $statusCode, string $message): never
    {
        http_response_code($statusCode);

        if ($this->wantsJson()) {
            Response::error($message, $statusCode);
            exit;
        }

        echo $this->render('errors/403', ['message' => $message]);
        exit;
    }

    protected function listRecords(array $filters = []): array
    {
        $user = $this->currentUser();

        if ($this->isTeacher($user) && $this->currentUserId() !== null) {
            return $this->requests->forUser((int) $this->currentUserId());
        }

        return $this->requests->all($filters);
    }

    protected function findRecord(int|string $id): ?array
    {
        return $this->requests->find($id)?->toArray();
    }
}
