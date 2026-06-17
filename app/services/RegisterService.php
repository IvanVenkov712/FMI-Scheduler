<?php
namespace App\Services;

use App\Models\User;
use App\Helpers\StringHelper;
use App\Repositories\UserRepository;
use Throwable;

// Registers a new user if it does not exist.
// Default role is student
// The user is active by default
class RegisterService extends BaseService
{
    private $user_repository;
    private $errors = [];
    private $error_message = 'Registration failed.';

    public function __construct($user_repository = null)
    {
        $this->user_repository = $this->dependency($user_repository, UserRepository::class);
    }

    public function register($data)
    {
        $this->errors = [];
        $this->error_message = 'Registration failed.';

        if (!is_array($data)) {
            return $this->fail('Invalid registration data.', ['registration' => 'Invalid registration data.']);
        }

        $username = StringHelper::normalize_string($data['username'] ?? null);
        $email = $this->normalize_email($data['email'] ?? null);
        $password = (string) ($data['password'] ?? '');

        if ($username === '') {
            return $this->fail('Please enter a username.', ['username' => 'This field is required.']);
        }

        if ($email === null) {
            return $this->fail('Please enter a valid email address.', ['email' => 'Enter a valid email address.']);
        }

        if ($password === '') {
            return $this->fail('Please enter a password.', ['password' => 'This field is required.']);
        }

        if ($this->user_repository->find_by_username($username) !== null) {
            return $this->fail('This username is already taken.', ['username' => 'This username is already taken.']);
        }

        if ($this->user_repository->find_by_email($email) !== null) {
            return $this->fail('An account with this email already exists.', ['email' => 'An account with this email already exists.']);
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

        try {
            $user_id = $this->user_repository->insert($user);
        } catch (Throwable $exception) {
            return $this->fail('We could not create your account. Please try again.', ['registration' => 'Could not create the account.']);
        }

        $created_user = $this->refresh($this->user_repository, $user_id);
        if ($created_user === null) {
            return $this->fail('We could not create your account. Please try again.', ['registration' => 'Could not load the created account.']);
        }

        return $created_user;
    }

    public function errors()
    {
        return $this->errors;
    }

    public function error_message()
    {
        return $this->error_message;
    }

    private function fail($message, $errors = [])
    {
        $this->error_message = $message;
        $this->errors = $errors;

        return null;
    }

}
