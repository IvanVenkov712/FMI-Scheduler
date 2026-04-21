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
        return $this->render('auth/register');
    }

    public function storeRegistration(): mixed
    {
        $data = $this->body();
        $result = $this->auth->registerForRoleSlug($data, ROLE_STUDENT);
        if (!($result['success'] ?? false)) {
            $errors = (array) ($result['errors'] ?? []);
            http_response_code(422);
            return $this->wantsJson()
                ? ['errors' => $errors]
                : $this->render('auth/register', ['errors' => $errors, 'old' => $data]);
        }
        $user = (array) ($result['user'] ?? []);
        SessionHelper::login($user);

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
