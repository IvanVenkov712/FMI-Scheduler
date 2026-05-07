<?php
namespace App\Helpers;

class SessionHelper
{
    public const USER_ID_KEY = 'user_id';
    public const USER_KEY = 'user';
    public const USER_ROLE_KEY = 'user_role';
    public const FLASH_KEY = '_flash';
    public const CSRF_TOKEN_KEY = '_csrf_token';

    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function has($key)
    {
        $this->start();

        return isset($_SESSION[$key]);
    }

    public function get($key, $default = null)
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function put($key, $value)
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function all()
    {
        $this->start();

        return $_SESSION;
    }

    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function forget($key)
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function regenerate($delete_old_session = true)
    {
        $this->start();
        session_regenerate_id($delete_old_session);
    }

    public function destroy()
    {
        $this->start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function login($user)
    {
        if ($user === null || !isset($user->id)) {
            return false;
        }

        $this->regenerate(true);
        $this->put(self::USER_ID_KEY, (int) $user->id);
        $this->put(self::USER_ROLE_KEY, $user->role ?? null);
        $this->put(self::USER_KEY, $this->serialize_user($user));

        return true;
    }

    public function logout()
    {
        $this->forget(self::USER_ID_KEY);
        $this->forget(self::USER_ROLE_KEY);
        $this->forget(self::USER_KEY);
        $this->regenerate(true);
    }

    public function is_authenticated()
    {
        return $this->get_user_id() !== null;
    }

    public function get_user_id()
    {
        $user_id = $this->get(self::USER_ID_KEY);

        return is_numeric($user_id) ? (int) $user_id : null;
    }

    public function get_user()
    {
        return $this->get(self::USER_KEY);
    }

    public function get_user_role()
    {
        $role = $this->get(self::USER_ROLE_KEY);

        return $role === null ? null : strtolower(trim((string) $role));
    }

    public function has_role($roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $current_role = $this->get_user_role();
        if ($current_role === null) {
            return false;
        }

        foreach ($roles as $role) {
            if ($current_role === strtolower(trim((string) $role))) {
                return true;
            }
        }

        return false;
    }

    public function flash($key, $value = null)
    {
        $this->start();

        if (func_num_args() === 1) {
            $value = $_SESSION[self::FLASH_KEY][$key] ?? null;
            unset($_SESSION[self::FLASH_KEY][$key]);

            return $value;
        }

        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    public function flashes()
    {
        return $this->pull(self::FLASH_KEY, []);
    }

    public function csrf_token()
    {
        $this->start();

        if (empty($_SESSION[self::CSRF_TOKEN_KEY])) {
            $_SESSION[self::CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    public function verify_csrf($token)
    {
        $this->start();

        return is_string($token)
            && isset($_SESSION[self::CSRF_TOKEN_KEY])
            && hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
    }

    private function serialize_user($user)
    {
        if (method_exists($user, 'to_array')) {
            $data = $user->to_array();
        } else {
            $data = get_object_vars($user);
        }

        unset($data['password_hash']);

        return $data;
    }
}
