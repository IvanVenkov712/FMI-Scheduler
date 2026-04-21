<?php

declare(strict_types=1);

class AuthController extends Controller
{
    public function login(): string
    {
        return $this->render('auth/login');
    }

    public function authenticate(): mixed
    {
        $data = $this->body();
        $login = trim((string) ($data['login'] ?? $data['email'] ?? $data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($login === '' || $password === '') {
            return $this->authError('Email/username and password are required.');
        }

        $user = $this->database()->fetch(
            'SELECT users.*, roles.slug AS role_slug, roles.name AS role_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             WHERE users.email = :login OR users.username = :login
             LIMIT 1',
            ['login' => $login]
        );

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return $this->authError('Invalid credentials.');
        }

        if ($user['status'] !== 'active') {
            return $this->authError('This account is not active.', 403);
        }

        $this->database()->execute('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $user['id']]);
        unset($user['password_hash'], $user['remember_token']);

        $_SESSION['user'] = $user;

        if ($this->wantsJson()) {
            return ['user' => $user, 'message' => 'Logged in successfully.'];
        }

        return $this->redirect('/dashboard');
    }

    public function register(): string
    {
        return $this->render('auth/register');
    }

    public function storeRegistration(): mixed
    {
        $data = $this->body();
        $errors = $this->requireFields($data, ['username', 'email', 'password', 'first_name', 'last_name']);

        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? $data['password'] ?? null)) {
            $errors['password_confirmation'] = 'Password confirmation does not match.';
        }

        if ($errors !== []) {
            http_response_code(422);
            return $this->wantsJson()
                ? ['errors' => $errors]
                : $this->render('auth/register', ['errors' => $errors, 'old' => $data]);
        }

        $role = $this->database()->fetch("SELECT id FROM roles WHERE slug = 'student' LIMIT 1");

        if ($role === null) {
            return $this->authError('Student role is not configured.', 500);
        }

        $id = $this->database()->insert(
            'INSERT INTO users (role_id, username, email, password_hash, first_name, last_name, phone, faculty_number, department, status)
             VALUES (:role_id, :username, :email, :password_hash, :first_name, :last_name, :phone, :faculty_number, :department, :status)',
            [
                'role_id' => $role['id'],
                'username' => trim((string) $data['username']),
                'email' => trim((string) $data['email']),
                'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
                'first_name' => trim((string) $data['first_name']),
                'last_name' => trim((string) $data['last_name']),
                'phone' => $data['phone'] ?? null,
                'faculty_number' => $data['faculty_number'] ?? null,
                'department' => $data['department'] ?? null,
                'status' => 'active',
            ]
        );

        $user = $this->database()->fetch(
            'SELECT users.id, users.role_id, users.username, users.email, users.first_name, users.last_name, users.status,
                    roles.slug AS role_slug, roles.name AS role_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             WHERE users.id = :id',
            ['id' => $id]
        );
        $_SESSION['user'] = $user;

        if ($this->wantsJson()) {
            http_response_code(201);
            return ['user' => $user, 'message' => 'Registration completed.'];
        }

        return $this->redirect('/dashboard');
    }

    public function logout(): mixed
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect('/dashboard', 302);
        }

        unset($_SESSION['user']);

        if ($this->wantsJson()) {
            return ['message' => 'Logged out successfully.'];
        }

        return $this->redirect('/login');
    }

    private function authError(string $message, int $status = 422): mixed
    {
        http_response_code($status);

        if ($this->wantsJson()) {
            return ['error' => $message];
        }

        return $this->render('auth/login', ['error' => $message, 'old' => $this->body()]);
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_starts_with((string) ($this->request()['path'] ?? ''), '/api')
            || $this->input('format') === 'json';
    }
}
