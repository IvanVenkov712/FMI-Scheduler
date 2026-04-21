<?php

declare(strict_types=1);

class UserRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?User
    {
        return User::find($id);
    }

    public function findByLogin(string $login): ?User
    {
        return User::findByLogin($login);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db()->fetch(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );

        return $row === null ? null : new User($row);
    }

    public function all(bool $onlyActive = false, string $roleSlug = ''): array
    {
        $sql = 'SELECT users.*
                FROM users
                LEFT JOIN roles ON roles.id = users.role_id
                WHERE 1=1';
        $params = [];

        if ($onlyActive) {
            $sql .= ' AND users.status = :status';
            $params['status'] = 'active';
        }

        if ($roleSlug !== '') {
            $sql .= ' AND roles.slug = :role_slug';
            $params['role_slug'] = $roleSlug;
        }

        $sql .= ' ORDER BY users.first_name ASC, users.last_name ASC, users.id ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): User => new User($row), $rows);
    }

    public function create(array $data): User
    {
        $user = new User($data);
        $user->save();

        return $user;
    }

    public function update(int|string $id, array $data): ?User
    {
        $user = $this->find($id);

        if ($user === null) {
            return null;
        }

        $user->fill($data);
        $user->save();

        return $user;
    }

    public function delete(int|string $id): bool
    {
        $user = $this->find($id);

        return $user?->delete() ?? false;
    }

    public function recordSuccessfulLogin(int $userId, ?string $rememberToken = null): int
    {
        $params = [
            'id' => $userId,
            'last_login_at' => date('Y-m-d H:i:s'),
        ];

        $sql = 'UPDATE users SET last_login_at = :last_login_at';

        if ($rememberToken !== null) {
            $sql .= ', remember_token = :remember_token';
            $params['remember_token'] = $rememberToken;
        }

        $sql .= ' WHERE id = :id';

        return $this->db()->execute($sql, $params);
    }

    public function activeTeachers(): array
    {
        return $this->all(true, 'teacher');
    }

    public function auditLogs(int $userId, int $limit = 50): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM audit_logs WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit),
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): AuditLog => new AuditLog($row), $rows);
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
