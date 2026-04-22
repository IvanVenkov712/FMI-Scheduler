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
        return $this->exists() ? (new RoleRepository())->users((int) $this->primaryValue()) : [];
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
