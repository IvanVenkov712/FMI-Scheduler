<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

// Registers a new user if it does not exist.
// Default role is student
// The user is active by default
class RegisterService
{
    private $user_repository;
    public function __construct($user_repository) {
        $this->user_repository = $user_repository ?? new UserRepository();
    }

    public function register($data)
    {
        if (!is_array($data)) {
            return null;
        }

        $username = $this->normalize_string($data['username'] ?? null);
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
            'first_name' => $this->normalize_string($data['first_name'] ?? ''),
            'last_name' => $this->normalize_string($data['last_name'] ?? ''),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'student',
            'is_active' => true,
        ]);

        $user_id = $this->user_repository->insert($user);

        return $this->user_repository->find_by_id((int) $user_id);
    }

    private function normalize_string($value)
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function normalize_email($value)
    {
        $email = trim((string) $value);
        if ($email === '') {
            return null;
        }

        $email = strtolower($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
