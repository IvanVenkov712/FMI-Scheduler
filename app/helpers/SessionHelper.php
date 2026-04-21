<?php

declare(strict_types=1);

class SessionHelper
{
    public static function start(array $config = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }

        if (isset($config['name']) && is_string($config['name']) && $config['name'] !== '') {
            session_name($config['name']);
        }

        $lifetime = (int) ($config['lifetime_minutes'] ?? 120);
        if ($lifetime > 0) {
            session_set_cookie_params([
                'lifetime' => $lifetime * 60,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        session_start();
    }

    public static function all(): array
    {
        self::start();

        return $_SESSION;
    }

    public static function has(string $key): bool
    {
        self::start();

        return self::exists($key, $_SESSION);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();

        return self::value($key, $_SESSION, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();

        self::put($key, $value, $_SESSION);
    }

    public static function remove(string $key): void
    {
        self::start();

        self::forget($key, $_SESSION);
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    public static function flash(string $key, mixed $value): void
    {
        self::set('_flash.' . $key, $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = self::get('_flash.' . $key, $default);
        self::remove('_flash.' . $key);

        return $value;
    }

    public static function keepFlash(string $key): void
    {
        $value = self::get('_flash.' . $key);

        if ($value !== null) {
            self::flash($key, $value);
        }
    }

    public static function setOldInput(array $input): void
    {
        self::set('_old_input', $input);
    }

    public static function old(?string $key = null, mixed $default = null): mixed
    {
        $old = self::get('_old_input', []);

        if ($key === null) {
            return is_array($old) ? $old : [];
        }

        return self::value($key, is_array($old) ? $old : [], $default);
    }

    public static function clearOldInput(): void
    {
        self::remove('_old_input');
    }

    public static function setErrors(array $errors): void
    {
        self::set('_errors', $errors);
    }

    public static function errors(): array
    {
        $errors = self::get('_errors', []);

        return is_array($errors) ? $errors : [];
    }

    public static function clearErrors(): void
    {
        self::remove('_errors');
    }

    public static function csrfToken(): string
    {
        $token = self::get('_csrf_token');

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            self::set('_csrf_token', $token);
        }

        return $token;
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        $sessionToken = self::get('_csrf_token');

        return is_string($token)
            && is_string($sessionToken)
            && hash_equals($sessionToken, $token);
    }

    public static function user(): ?array
    {
        $user = self::get('user');

        return is_array($user) ? $user : null;
    }

    public static function isAuthenticated(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        self::set('user', $user);
    }

    public static function logout(): void
    {
        self::remove('user');
    }

    private static function exists(string $key, array $source): bool
    {
        $segments = explode('.', $key);
        $current = $source;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];
        }

        return true;
    }

    private static function value(string $key, array $source, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current = $source;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private static function put(string $key, mixed $value, array &$source): void
    {
        $segments = explode('.', $key);
        $current = &$source;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current = $value;
    }

    private static function forget(string $key, array &$source): void
    {
        $segments = explode('.', $key);
        $last = array_pop($segments);
        $current = &$source;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return;
            }

            $current = &$current[$segment];
        }

        if ($last !== null && is_array($current) && array_key_exists($last, $current)) {
            unset($current[$last]);
        }
    }
}
