<?php

declare(strict_types=1);

class AuthMiddleware
{
    public function handle(array $request): bool
    {
        SessionHelper::start();

        $user = self::resolveUser();

        if ($user === null) {
            $this->deny($request, 401, 'Authentication is required.');
        }

        if (($user['status'] ?? STATUS_ACTIVE) !== STATUS_ACTIVE) {
            SessionHelper::logout();
            $this->deny($request, 403, 'This account is not active.');
        }

        return true;
    }

    public static function resolveUser(): ?array
    {
        SessionHelper::start();

        $user = SessionHelper::user();
        if ($user === null) {
            return null;
        }

        $resolved = self::hydrateRoleData($user);

        if ($resolved !== $user) {
            SessionHelper::login($resolved);
        }

        return $resolved;
    }

    public static function normalizeRole(?string $role): string
    {
        $role = strtolower(trim((string) $role));

        return str_replace([' ', '-'], '_', $role);
    }

    public static function userHasRole(array $user, array|string $roles): bool
    {
        $roles = array_map([self::class, 'normalizeRole'], (array) $roles);
        $roleSlug = self::normalizeRole((string) ($user['role_slug'] ?? ''));
        $roleName = self::normalizeRole((string) ($user['role_name'] ?? ''));
        $roleId = (int) ($user['role_id'] ?? 0);

        foreach ($roles as $role) {
            if ($role === '') {
                continue;
            }

            if ($roleSlug === $role || $roleName === $role) {
                return true;
            }

            if ($role === 'system_admin' && $roleId === 1) {
                return true;
            }
        }

        return false;
    }

    public static function userHasPermission(array $user, string $permission): bool
    {
        $permissions = self::permissionsFromUser($user);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    protected function deny(array $request, int $statusCode, string $message): never
    {
        if ($this->expectsJson($request)) {
            Response::error($message, $statusCode);
            exit;
        }

        if ($statusCode === 401) {
            Response::redirect('/login');
        }

        http_response_code($statusCode);

        $view = new View();
        $errorView = 'errors/' . $statusCode;

        echo $view->exists($errorView)
            ? $view->render($errorView, ['message' => $message])
            : $message;

        exit;
    }

    protected function expectsJson(array $request): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $path = (string) ($request['path'] ?? '');

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || str_starts_with($path, '/api');
    }

    private static function hydrateRoleData(array $user): array
    {
        if (!empty($user['role_slug']) && array_key_exists('permissions', $user)) {
            return $user;
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        if ($roleId <= 0) {
            return $user;
        }

        try {
            $database = Database::getInstance(Database::configFromFile());
            $role = $database->fetch(
                'SELECT id, slug, name, permissions FROM roles WHERE id = :id LIMIT 1',
                ['id' => $roleId]
            );
        } catch (Throwable) {
            return $user;
        }

        if ($role === null) {
            return $user;
        }

        $user['role_slug'] = $role['slug'] ?? ($user['role_slug'] ?? null);
        $user['role_name'] = $role['name'] ?? ($user['role_name'] ?? null);
        $user['permissions'] = $role['permissions'] ?? ($user['permissions'] ?? []);

        return $user;
    }

    private static function permissionsFromUser(array $user): array
    {
        $permissions = $user['permissions'] ?? [];

        if (is_string($permissions) && $permissions !== '') {
            $decoded = json_decode($permissions, true);
            $permissions = is_array($decoded) ? $decoded : [$permissions];
        }

        if (!is_array($permissions)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $permissions), static fn (string $permission): bool => $permission !== ''));
    }
}
