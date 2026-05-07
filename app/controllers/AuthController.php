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
        $this->login_service = $login_service ?? new LoginService();
        $this->register_service = $register_service ?? new RegisterService();
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

        $user = $this->login_service->login($data['identity'], $data['password']);
        if ($user === null) {
            return $this->error('Invalid credentials or inactive user.', 401);
        }

        $this->session->login($user);

        if (!$this->wants_json()) {
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
        $errors = $this->require_fields($data, ['username', 'email', 'password']);
        if ($errors !== []) {
            return $this->error('Invalid registration data.', 422, $errors);
        }

        $user = $this->register_service->register($data);
        if ($user === null) {
            return $this->error('Registration failed.', 422);
        }

        $this->session->login($user);

        return $this->success(['user' => $this->session->get_user()], 201);
    }

    public function logout()
    {
        $this->session->logout();

        if (!$this->wants_json()) {
            return $this->redirect('/login');
        }

        return $this->success(['logged_out' => true]);
    }

    public function me()
    {
        if (!$this->session->is_authenticated()) {
            return $this->error('Authentication required.', 401);
        }

        return $this->success(['user' => $this->session->get_user()]);
    }
}
