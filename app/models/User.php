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

        return $roleId === null ? null : Role::find((int) $roleId);
    }

    public function createdEvents(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT * FROM events WHERE created_by = :user_id ORDER BY starts_at DESC',
            ['user_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function examRequests(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT * FROM exam_requests WHERE requested_by = :user_id ORDER BY created_at DESC',
            ['user_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): ExamRequest => new ExamRequest($row), $rows);
    }

    public function hostedSessions(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT * FROM online_sessions WHERE host_user_id = :user_id ORDER BY created_at DESC',
            ['user_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): OnlineSession => new OnlineSession($row), $rows);
    }

    public function auditLogs(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT * FROM audit_logs WHERE user_id = :user_id ORDER BY created_at DESC',
            ['user_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): AuditLog => new AuditLog($row), $rows);
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
        $row = Database::getInstance(Database::configFromFile())->fetch(
            'SELECT * FROM users WHERE email = :email_login OR username = :username_login LIMIT 1',
            [
                'email_login' => $login,
                'username_login' => $login,
            ]
        );

        return $row === null ? null : new self($row);
    }
}
