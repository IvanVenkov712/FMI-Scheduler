<?php

declare(strict_types=1);

class UserController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly UserService $usersService = new UserService()
    ) {
        parent::__construct($request);
    }

    protected string $viewPath = 'users';
    protected string $entityName = 'user';
    protected array $fillable = [
        'role_id', 'username', 'email', 'password_hash', 'first_name', 'last_name',
        'phone', 'faculty_number', 'department', 'status',
    ];
    protected array $required = ['username', 'email', 'first_name', 'last_name'];
    protected array $integerFields = ['role_id'];
    protected array $listFilters = ['role_id', 'status', 'department'];

    public function profile(): string
    {
        $id = $_SESSION['user']['id'] ?? null;

        if ($id === null) {
            return $this->render('errors/403', ['message' => 'Login is required.']);
        }

        return $this->render('users/profile', ['user' => $this->publicUser($this->findOrFail($id))]);
    }

    public function manage(): mixed
    {
        return $this->index();
    }

    public function store(): mixed
    {
        $data = $this->payload();
        $result = $this->usersService->create($data, $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create', $this->formData());
        }

        /** @var User|null $user */
        $user = $result['user'] ?? null;

        return $this->storedResponse($user?->publicData() ?? []);
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->usersService->update($id, $data, $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/edit', array_merge($this->formData(), [
                $this->entityName => array_merge($this->findOrFail($id), $data),
            ]));
        }

        /** @var User|null $user */
        $user = $result['user'] ?? null;

        return $this->updatedResponse($user?->publicData() ?? $this->publicUser($this->findOrFail($id)));
    }

    public function delete(int|string $id): mixed
    {
        $deleted = $this->usersService->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('User not found.');
        }

        return $this->deletedResponse();
    }

    public function resetPassword(int|string $id): mixed
    {
        $result = $this->usersService->resetPassword($id, $this->currentUserId());

        if (!($result['success'] ?? false)) {
            if ($this->wantsJson()) {
                http_response_code(404);
                return ['errors' => $result['errors'] ?? ['user' => ['User not found.']]];
            }

            SessionHelper::flash('error', 'Password reset failed.');
            return $this->redirect($this->returnTo($id));
        }

        /** @var User|null $user */
        $user = $result['user'] ?? null;
        $temporaryPassword = (string) ($result['temporary_password'] ?? '');
        $name = $user instanceof User ? trim($user->fullName()) : '';
        $label = $name !== '' ? $name : (string) ($user?->get('username') ?? 'user');
        $message = sprintf('Temporary password for %s: %s', $label, $temporaryPassword);

        if ($this->wantsJson()) {
            return [
                'message' => 'Password reset successfully.',
                'temporary_password' => $temporaryPassword,
                'user' => $user?->publicData(),
            ];
        }

        SessionHelper::flash('success', $message);

        return $this->redirect($this->returnTo($id));
    }

    public function details(int|string $id): mixed
    {
        return $this->respond('users/profile', ['user' => $this->publicUser($this->findOrFail($id))]);
    }

    protected function rows(): array
    {
        $rows = parent::rows();

        return array_map(fn (array $user): array => $this->publicUser($user), $rows);
    }

    protected function formData(): array
    {
        return [
            'roles' => $this->usersService->roles(),
        ];
    }

    private function publicUser(array $user): array
    {
        if (!isset($user['role_name']) && isset($user['role_id'])) {
            $user['role_name'] = (new User($user))->role()?->get('name');
        }

        unset($user['password_hash'], $user['remember_token']);

        return $user;
    }

    private function returnTo(int|string $id): string
    {
        $returnTo = (string) $this->input('return_to', '/users/' . $id . '/edit');

        if ($returnTo === '' || !str_starts_with($returnTo, '/')) {
            return '/users/' . $id . '/edit';
        }

        return $returnTo;
    }

    protected function listRecords(array $filters = []): array
    {
        return $this->usersService->listing($filters);
    }

    protected function findRecord(int|string $id): ?array
    {
        return $this->usersService->find($id)?->toArray();
    }
}
