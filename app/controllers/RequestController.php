<?php

declare(strict_types=1);

class RequestController extends CrudController
{
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
        $_POST['requested_by'] ??= $this->currentUserId();
        return parent::store();
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

        return parent::update($id);
    }

    public function delete(int|string $id): mixed
    {
        $request = $this->findOrFail($id);
        $this->authorizeRequestAccess($request);

        return parent::delete($id);
    }

    public function review(int|string $id): mixed
    {
        $this->authorizeReview();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->respond('requests/review', ['request' => $this->findOrFail($id)]);
        }

        $data = $this->payload();
        $status = (string) ($data['status'] ?? '');

        if (!in_array($status, ['approved', 'rejected', 'scheduled', 'cancelled'], true)) {
            return $this->validationResponse(['status' => 'Invalid review status.'], $data, 'requests/review', [
                'request' => $this->findOrFail($id),
            ]);
        }

        $this->updateRow($id, [
            'status' => $status,
            'reviewed_by' => $this->currentUserId(),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'notes' => $data['notes'] ?? $this->findOrFail($id)['notes'],
        ]);

        return $this->updatedResponse($this->findOrFail($id));
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
