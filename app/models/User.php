<?php

declare(strict_types=1);

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'role_id',
        'username',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'phone',
        'faculty_number',
        'department',
        'status',
        'last_login_at',
        'remember_token',
    ];

    public function role(): ?Role
    {
        $roleId = $this->get('role_id');

        return $roleId === null ? null : (new RoleRepository())->find((int) $roleId);
    }

    public function createdEvents(): array
    {
        return $this->exists() ? (new UserRepository())->createdEvents((int) $this->primaryValue()) : [];
    }

    public function examRequests(): array
    {
        return $this->exists() ? (new UserRepository())->examRequests((int) $this->primaryValue()) : [];
    }

    public function hostedSessions(): array
    {
        return $this->exists() ? (new UserRepository())->hostedSessions((int) $this->primaryValue()) : [];
    }

    public function auditLogs(): array
    {
        return $this->exists() ? (new UserRepository())->auditLogs((int) $this->primaryValue()) : [];
    }

    public function fullName(): string
    {
        return trim((string) $this->get('first_name', '') . ' ' . (string) $this->get('last_name', ''));
    }

    public function isActive(): bool
    {
        return $this->get('status', 'active') === 'active';
    }

    public function hasRole(string $slug): bool
    {
        $role = $this->role();

        return $role !== null && $role->get('slug') === $slug;
    }

    public function publicData(): array
    {
        $data = $this->toArray();
        unset($data['password_hash'], $data['remember_token']);

        return $data;
    }

    public static function findByLogin(string $login): ?self
    {
        return (new UserRepository())->findByLogin($login);
    }
}
