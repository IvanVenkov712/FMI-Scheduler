<?php
namespace App\Services;

use App\Repositories\UserRepository;

class LoginService 
{
    private $user_repository;

    public function __construct($user_repository = null)
    {
        $this->user_repository = $user_repository ?? new UserRepository();
    }

    public function login($identity, $password)
    {
        $user = $this->user_repository->find_user_by_identity($identity);
        if ($user === null || !$this->is_user_active($user)) {
            return null;
        }

        if (!$this->verify_password($password, $user->password_hash)) {
            return null;
        }
        return $user;
    }
    
    private function verify_password($plain_password, $password_hash)
    {
        if ($plain_password === null || $password_hash === null || $password_hash === '') {
            return false;
        }

        return password_verify($plain_password, $password_hash);
    }

    private function is_user_active($user)
    {
        return isset($user->is_active) && (bool) $user->is_active;
    }
}