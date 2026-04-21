<?php

declare(strict_types=1);

class Role extends Model
{
    protected string $table = 'roles';
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
    ];

    public function users(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT * FROM users WHERE role_id = :role_id ORDER BY last_name ASC, first_name ASC',
            ['role_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): User => new User($row), $rows);
    }

    public function permissions(): array
    {
        $value = $this->get('permissions');

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
