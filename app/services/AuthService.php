<?php

declare(strict_types=1);

class AuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly RoleRepository $roles = new RoleRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function attemptFromInput(array $input, bool $remember = false): array
    {
        $login = trim((string) ($input['login'] ?? $input['email'] ?? $input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        return $this->attempt($login, $password, $remember);
    }

    public function attempt(string $login, string $password, bool $remember = false): array
    {
        $result = $this->validation->validate(
            ['login' => $login, 'password' => $password],
            ['login' => 'required|string', 'password' => 'required|string|min:6']
        );

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        $user = $this->users->findByLogin($login);
        if ($user === null || !$user->isActive()) {
            return ['success' => false, 'errors' => ['login' => ['Invalid credentials.']]];
        }

        if (!password_verify($password, (string) $user->get('password_hash', ''))) {
            $this->auditLogs->record('login_failed', 'user', $user->get('id'), null, ['login' => $login]);

            return ['success' => false, 'errors' => ['login' => ['Invalid credentials.']]];
        }

        $publicUser = $user->publicData();
        SessionHelper::login($publicUser);

        $rememberToken = null;
        if ($remember) {
            $rememberToken = bin2hex(random_bytes(32));
        }

        $this->users->recordSuccessfulLogin((int) $user->get('id'), $rememberToken);
        $this->auditLogs->record('login', 'user', $user->get('id'), null, ['remember' => $remember], (int) $user->get('id'));

        return ['success' => true, 'user' => $publicUser];
    }

    public function register(array $data): array
    {
        $data = $this->validation->normalizeIntegers($data, ['role_id']);
        $result = $this->validation->validate($data, [
            'role_id' => 'required|integer',
            'username' => 'required|string|min:3|max:80',
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:6|max:255',
            'password_confirmation' => 'required|same:password',
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if ($this->users->findByLogin((string) $data['username']) !== null) {
            return ['success' => false, 'errors' => ['username' => ['Username is already taken.']]];
        }

        if ($this->users->findByEmail((string) $data['email']) !== null) {
            return ['success' => false, 'errors' => ['email' => ['Email is already taken.']]];
        }

        unset($data['password_confirmation']);
        $data['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        $data['status'] = $data['status'] ?? 'inactive';

        $user = $this->users->create($data);
        $this->auditLogs->record('register', 'user', $user->get('id'), null, $user->publicData(), (int) $user->get('id'));

        return ['success' => true, 'user' => $user->publicData()];
    }

    public function registerForRoleSlug(array $data, string $roleSlug): array
    {
        $roleId = $this->roles->findIdBySlug($roleSlug);
        if ($roleId === null) {
            return ['success' => false, 'errors' => ['role' => ['Requested role is not configured.']]];
        }

        $data['role_id'] = $roleId;

        return $this->register($data);
    }

    public function logout(): void
    {
        $user = SessionHelper::user();
        if (is_array($user) && isset($user['id'])) {
            $this->auditLogs->record('logout', 'user', $user['id'], null, null, (int) $user['id']);
        }

        SessionHelper::logout();
    }

    public function currentUser(): ?User
    {
        $sessionUser = SessionHelper::user();

        return isset($sessionUser['id']) ? $this->users->find((int) $sessionUser['id']) : null;
    }

    public function check(): bool
    {
        return $this->currentUser() !== null;
    }

    public function requireRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        $user = $this->currentUser();

        if ($user === null) {
            return false;
        }

        foreach ($roles as $role) {
            if ($user->hasRole((string) $role)) {
                return true;
            }
        }

        return false;
    }
}
