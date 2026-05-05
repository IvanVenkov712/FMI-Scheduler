<?php
namespace App\Services;

use App\Repositories\UserRepository;

class AuthorizationService
{
    private $user_repository;

    public function __construct($user_repository = null)
    {
        $this->user_repository = $user_repository ?? new UserRepository();
    }

    public function get_role($identity)
    {
        $user = $this->user_repository->find_user_by_identity($identity);

        if ($user === null || !$this->is_user_active($user)) {
            return null;
        }

        return $this->normalize_role($user->role ?? null);
    }

    public function has_role($identity, $role)
    {
        $user_role = $this->get_role($identity);
        $required_role = $this->normalize_role($role);

        if ($user_role === null || $required_role === null) {
            return false;
        }

        return $user_role === $required_role;
    }

    public function has_any_role($identity, $roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $user_role = $this->get_role($identity);
        if ($user_role === null) {
            return false;
        }

        foreach ($roles as $role) {
            if ($user_role === $this->normalize_role($role)) {
                return true;
            }
        }

        return false;
    }

    public function is_authorized($identity, $allowed_roles)
    {
        return $this->has_any_role($identity, $allowed_roles);
    }

    public function is_admin($identity)
    {
        return $this->has_role($identity, 'admin');
    }

    public function is_teacher($identity)
    {
        return $this->has_role($identity, 'teacher');
    }

    public function is_student($identity)
    {
        return $this->has_role($identity, 'student');
    }

    public function is_schedule_manager($identity)
    {
        return $this->has_role($identity, 'schedule_manager');
    }

    public function is_active($identity)
    {
        $user = $this->user_repository->find_user_by_identity($identity);

        return $user !== null && $this->is_user_active($user);
    }

    private function normalize_role($role)
    {
        if ($role === null) {
            return null;
        }

        $role = strtolower(trim((string) $role));

        return $role === '' ? null : $role;
    }

    private function is_user_active($user)
    {
        return isset($user->is_active) && (bool) $user->is_active;
    }
}
