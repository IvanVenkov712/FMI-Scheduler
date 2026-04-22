<?php

declare(strict_types=1);

class AuthController extends Controller
{
    public function __construct(
        array $request = [],
        private readonly AuthService $auth = new AuthService()
    ) {
        parent::__construct($request);
    }

    public function login(): string
    {
        return $this->render('auth/login');
    }

    public function authenticate(): mixed
    {
        $data = $this->body();
        $login = trim((string) ($data['login'] ?? $data['email'] ?? $data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $result = $this->auth->attempt($login, $password);

        if (!($result['success'] ?? false)) {
            $errors = (array) ($result['errors'] ?? []);
            $message = (string) (($errors['login'][0] ?? null) ?? 'Invalid credentials.');
            return $this->authError($message);
        }

        $user = (array) ($result['user'] ?? []);

        if ($this->wantsJson()) {
            return ['user' => $user, 'message' => 'Logged in successfully.'];
        }

        return $this->redirect('/dashboard');
    }

    public function register(): string
    {
        http_response_code(403);

        return $this->render('errors/403', [
            'message' => 'Self-service registration is disabled. Accounts are created and managed by administrators.',
        ]);
    }

    public function storeRegistration(): mixed
    {
        http_response_code(403);

        if ($this->wantsJson()) {
            return [
                'error' => 'Self-service registration is disabled. Please contact an administrator for account creation or activation.',
            ];
        }

        return $this->render('errors/403', [
            'message' => 'Self-service registration is disabled. Please contact an administrator for account creation or activation.',
        ]);
    }

    public function logout(): mixed
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect('/dashboard', 302);
        }

        $this->auth->logout();

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
