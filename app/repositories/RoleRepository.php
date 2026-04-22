<?php

declare(strict_types=1);

class RoleRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function all(): array
    {
        return $this->db()->fetchAll('SELECT id, name, slug FROM roles ORDER BY name ASC');
    }

    public function find(int|string $id): ?Role
    {
        return (new GenericModelRepository($this->database))->find(Role::class, $id);
    }

    public function users(int $roleId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM users WHERE role_id = :role_id ORDER BY last_name ASC, first_name ASC',
            ['role_id' => $roleId]
        );

        return array_map(static fn (array $row): User => new User($row), $rows);
    }

    public function findIdBySlug(string $slug): ?int
    {
        $row = $this->db()->fetch(
            'SELECT id FROM roles WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        );

        return $row === null ? null : (int) $row['id'];
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
