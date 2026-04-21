<?php

declare(strict_types=1);

class AdminMiddleware extends AuthMiddleware
{
    public function handle(array $request): bool
    {
        parent::handle($request);

        $user = self::resolveUser();

        if ($user === null) {
            $this->deny($request, 401, 'Authentication is required.');
        }

        $isAdmin = self::userHasRole($user, [ROLE_SYSTEM_ADMIN, 'system_admin'])
            || self::userHasPermission($user, '*');

        if (!$isAdmin) {
            $this->deny($request, 403, 'Administrator access is required.');
        }

        return true;
    }
}
