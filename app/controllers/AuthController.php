<?php

declare(strict_types=1);

class AuthController extends Controller
{
    private const REGISTRATION_DISABLED_MESSAGE =
        'Self-service registration is disabled. Accounts are created and managed by administrators.';

    private const REGISTRATION_DISABLED_ERROR =
        'Self-service registration is disabled. Please contact an administrator for account creation or activation.';

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
        $result = $this->auth->attemptFromInput($this->body());

        if (!($result['success'] ?? false)) {
            $errors = (array) ($result['errors'] ?? []);
            $message = (string) (($errors['login'][0] ?? null) ?? 'Invalid credentials.');
            return $this->authErrorResponse($message);
        }

        $user = (array) ($result['user'] ?? []);

        return $this->loginSuccessResponse($user);
    }

    public function register(): string
    {
        http_response_code(403);

        return $this->render('errors/403', [
            'message' => self::REGISTRATION_DISABLED_MESSAGE,
        ]);
    }

    public function storeRegistration(): mixed
    {
        return $this->registrationDisabledResponse();
    }

    public function logout(): mixed
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->redirect('/dashboard', 302);
        }

        $this->auth->logout();

        return $this->logoutResponse();
    }

    private function loginSuccessResponse(array $user): mixed
    {
        if ($this->wantsJson()) {
            return ['user' => $user, 'message' => 'Logged in successfully.'];
        }

        return $this->redirect('/dashboard');
    }

    private function logoutResponse(): mixed
    {
        if ($this->wantsJson()) {
            return ['message' => 'Logged out successfully.'];
        }

        return $this->redirect('/login');
    }

    private function registrationDisabledResponse(): mixed
    {
        http_response_code(403);

        if ($this->wantsJson()) {
            return ['error' => self::REGISTRATION_DISABLED_ERROR];
        }

        return $this->render('errors/403', [
            'message' => self::REGISTRATION_DISABLED_ERROR,
        ]);
    }

    private function authErrorResponse(string $message, int $status = 422): mixed
    {
        http_response_code($status);

        if ($this->wantsJson()) {
            return ['error' => $message];
        }

        return $this->render('auth/login', ['error' => $message, 'old' => $this->body()]);
    }
}
