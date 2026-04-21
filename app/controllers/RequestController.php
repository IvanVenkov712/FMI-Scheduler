<?php

declare(strict_types=1);

class RequestController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly RequestService $requests = new RequestService()
    ) {
        parent::__construct($request);
    }

    protected string $table = 'exam_requests';
    protected string $viewPath = 'requests';
    protected string $entityName = 'request';
    protected array $fillable = [
        'requested_by', 'event_id', 'course_name', 'students_count', 'preferred_room_id',
        'preferred_delivery_mode', 'preferred_starts_at', 'preferred_ends_at',
        'required_resources', 'notes', 'status', 'reviewed_by', 'reviewed_at',
    ];
    protected array $required = ['course_name', 'students_count'];
    protected array $integerFields = ['requested_by', 'event_id', 'students_count', 'preferred_room_id', 'reviewed_by'];
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
                    'SELECT * FROM exam_requests WHERE requested_by = :requested_by ORDER BY created_at DESC',
                    ['requested_by' => $this->currentUserId()]
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

        return $this->redirect($this->indexUrl());
    }

    protected function formData(): array
    {
        return [
            'rooms' => $this->database()->fetchAll('SELECT id, code, name, capacity FROM rooms ORDER BY code ASC'),
            'resources' => $this->database()->fetchAll('SELECT * FROM resources ORDER BY name ASC'),
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
}
