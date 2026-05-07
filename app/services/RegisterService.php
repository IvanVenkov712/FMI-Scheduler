<?php
namespace App\Services;

use App\Models\User;
use App\Helpers\StringHelper;
use App\Repositories\UserRepository;

// Registers a new user if it does not exist.
// Default role is student
// The user is active by default
class RegisterService extends BaseService
{
    private $user_repository;

    public function __construct($user_repository = null)
    {
        $this->user_repository = $this->dependency($user_repository, UserRepository::class);
    }

    public function register($data)
    {
        if (!is_array($data)) {
            return null;
        }

        $username = StringHelper::normalize_string($data['username'] ?? null);
        $email = $this->normalize_email($data['email'] ?? null);
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $email === null || $password === '') {
            return null;
        }

        if ($this->user_repository->find_by_username($username) !== null) {
            return null;
        }

        if ($this->user_repository->find_by_email($email) !== null) {
            return null;
        }

        $user = new User([
            'username' => $username,
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'student',
            'is_active' => true,
        ]);

        $user_id = $this->user_repository->insert($user);

        return $this->refresh($this->user_repository, $user_id);
    }

}
