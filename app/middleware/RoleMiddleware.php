<?php

declare(strict_types=1);

class RoleMiddleware extends AuthMiddleware
{
    private array $roles;
    private array $permissions;
    private bool $allowAdminOverride;

    public function __construct(array|string $roles = [], array|string $permissions = [], bool $allowAdminOverride = true)
    {
        $this->roles = array_values(array_filter(array_map(
            [self::class, 'normalizeRole'],
            (array) $roles
        )));
        $this->permissions = array_values(array_filter(array_map('strval', (array) $permissions)));
        $this->allowAdminOverride = $allowAdminOverride;
    }

    public function handle(array $request): bool
    {
        parent::handle($request);

        $user = self::resolveUser();

        if ($user === null) {
            $this->deny($request, 401, 'Authentication is required.');
        }

        if ($this->allowAdminOverride && AdminMiddleware::userHasRole($user, [ROLE_SYSTEM_ADMIN, 'system_admin'])) {
            return true;
        }

        if ($this->roles !== [] && !self::userHasRole($user, $this->roles)) {
            $this->deny($request, 403, 'You do not have the required role for this action.');
        }

        foreach ($this->permissions as $permission) {
            if (!self::userHasPermission($user, $permission)) {
                $this->deny($request, 403, 'You do not have permission for this action.');
            }
        }

        return true;
    }

    public static function forRoles(array|string $roles, bool $allowAdminOverride = true): self
    {
        return new self($roles, [], $allowAdminOverride);
    }

    public static function forPermissions(array|string $permissions, bool $allowAdminOverride = true): self
    {
        return new self([], $permissions, $allowAdminOverride);
    }

    public static function forRolesAndPermissions(
        array|string $roles,
        array|string $permissions,
        bool $allowAdminOverride = true
    ): self {
        return new self($roles, $permissions, $allowAdminOverride);
    }
}
