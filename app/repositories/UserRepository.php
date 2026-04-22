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
        return (new GenericModelRepository($this->database))->find(User::class, $id);
    }

    public function findByLogin(string $login): ?User
    {
        $row = $this->db()->fetch(
            'SELECT * FROM users WHERE email = :email_login OR username = :username_login LIMIT 1',
            [
                'email_login' => $login,
                'username_login' => $login,
            ]
        );

        return $row === null ? null : new User($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db()->fetch(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );

        return $row === null ? null : new User($row);
    }

    public function findByFacultyNumber(string $facultyNumber): ?User
    {
        $row = $this->db()->fetch(
            'SELECT * FROM users WHERE faculty_number = :faculty_number LIMIT 1',
            ['faculty_number' => $facultyNumber]
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

    public function listing(array $filters = []): array
    {
        $sql = 'SELECT users.*
                FROM users
                LEFT JOIN roles ON roles.id = users.role_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['role_id'])) {
            $sql .= ' AND users.role_id = :role_id';
            $params['role_id'] = (int) $filters['role_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND users.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['department'])) {
            $sql .= ' AND users.department = :department';
            $params['department'] = $filters['department'];
        }

        $sql .= ' ORDER BY users.last_name ASC, users.first_name ASC, users.id ASC';

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

    public function studentRoleId(): ?int
    {
        $row = $this->db()->fetch('SELECT id FROM roles WHERE slug = :slug LIMIT 1', ['slug' => 'student']);

        return $row === null ? null : (int) $row['id'];
    }

    public function allStudents(): array
    {
        return $this->db()->fetchAll(
            'SELECT users.id, users.role_id, users.username, users.email, users.first_name, users.last_name,
                    users.phone, users.faculty_number, users.department, users.status, users.created_at, users.updated_at,
                    GROUP_CONCAT(student_groups.code ORDER BY student_groups.code SEPARATOR ",") AS group_codes
             FROM users
             INNER JOIN roles ON roles.id = users.role_id AND roles.slug = :role_slug
             LEFT JOIN student_group_members ON student_group_members.user_id = users.id
             LEFT JOIN student_groups ON student_groups.id = student_group_members.student_group_id
             GROUP BY users.id
             ORDER BY users.last_name ASC, users.first_name ASC, users.id ASC',
            ['role_slug' => 'student']
        );
    }

    public function auditLogs(int $userId, int $limit = 50): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM audit_logs WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit),
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): AuditLog => new AuditLog($row), $rows);
    }

    public function hostedSessions(int $userId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM online_sessions WHERE host_user_id = :user_id ORDER BY created_at DESC',
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): OnlineSession => new OnlineSession($row), $rows);
    }

    public function createdEvents(int $userId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM events WHERE created_by = :user_id ORDER BY starts_at DESC',
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function examRequests(int $userId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM exam_requests WHERE requested_by = :user_id ORDER BY created_at DESC',
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): ExamRequest => new ExamRequest($row), $rows);
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
