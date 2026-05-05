<?php
namespace App\Services;

use App\Repositories\UserRepository;

class RegisterService
{
    private $user_repository;
    public function __construct($user_repository) {
        $this->user_repository = $user_repository ?? new UserRepository();
    }
}