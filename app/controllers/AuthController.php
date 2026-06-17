<?php
namespace App\Controllers;

use App\Services\LoginService;
use App\Services\RegisterService;

class AuthController extends BaseController
{
    private $login_service;
    private $register_service;

    public function __construct($request = [], $view = null, $session = null, $login_service = null, $register_service = null)
    {
        parent::__construct($request, $view, $session);
        $this->login_service = $login_service;
        $this->register_service = $register_service;
    }

    public function login_form()
    {
        return $this->respond('auth/login', []);
    }

    public function login()
    {
        $data = $this->payload();
        $errors = $this->require_fields($data, ['identity', 'password']);
        if ($errors !== []) {
            return $this->error('Invalid login data.', 422, $errors);
        }

        $user = $this->login_service()->login($data['identity'], $data['password']);
        if ($user === null) {
            return $this->error('Invalid credentials or inactive user.', 401);
        }

        $this->session->login($user);

        if (!$this->wants_json()) {
            $this->audit_success(['user' => $this->session->get_user()]);
            return $this->redirect('/dashboard');
        }

        return $this->success(['user' => $this->session->get_user()]);
    }

    public function register_form()
    {
        return $this->respond('auth/register', []);
    }

    public function register()
    {
        $data = $this->payload();
        $errors = $this->require_fields($data, ['username', 'email', 'password', 'password_confirmation']);
        if ($errors === [] && (string) $data['password'] !== (string) $data['password_confirmation']) {
            if (!$this->wants_json()) {
                return $this->error('Passwords do not match.', 422);
            }

            $errors['password_confirmation'] = 'Password confirmation does not match.';
        }

        if ($errors !== []) {
            if (!$this->wants_json()) {
                return $this->error('Please fill in all required fields.', 422);
            }

            return $this->error('Invalid registration data.', 422, $errors);
        }

        $register_service = $this->register_service();
        $user = $register_service->register($data);
        if ($user === null) {
            return $this->error(
                $register_service->error_message(),
                422,
                $this->wants_json() ? $register_service->errors() : []
            );
        }

        if (!$this->wants_json()) {
            $this->audit_success(['registered_user_id' => $user->id ?? null]);
            $this->session->flash('success', 'Registration successful');
            return $this->redirect('/login');
        }

        $this->session->login($user);

        return $this->success(['user' => $this->session->get_user()], 201);
    }

    public function logout()
    {
        $this->audit_success(['logged_out' => true]);
        $this->session->logout();

        if (!$this->wants_json()) {
            return $this->redirect('/login');
        }

        return $this->json(['success' => true, 'data' => ['logged_out' => true]]);
    }

    public function me()
    {
        if (!$this->session->is_authenticated()) {
            return $this->error('Authentication required.', 401);
        }

        return $this->success(['user' => $this->session->get_user()]);
    }

    private function login_service()
    {
        return $this->login_service ??= new LoginService();
    }

    private function register_service()
    {
        return $this->register_service ??= new RegisterService();
    }
}
