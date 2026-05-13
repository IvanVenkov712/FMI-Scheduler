<?php
namespace App\Helpers;

class SessionHelper
{
    public const USER_ID_KEY = 'user_id';
    public const USER_KEY = 'user';
    public const USER_ROLE_KEY = 'user_role';
    public const FLASH_KEY = '_flash';

    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $this->ensure_writable_session_path();
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
        return $this->normalize_role($this->get(self::USER_ROLE_KEY));
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
            if ($current_role === $this->normalize_role($role)) {
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

    private function normalize_role($role)
    {
        return $role === null ? null : strtolower(trim((string) $role));
    }

    private function ensure_writable_session_path()
    {
        if (ini_get('session.save_handler') !== 'files') {
            return;
        }

        $path = session_save_path();
        $usable = $path !== '' && is_dir($path) && is_writable($path);
        if ($usable) {
            return;
        }

        $fallback = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
        if (!is_dir($fallback)) {
            mkdir($fallback, 0775, true);
        }

        session_save_path($fallback);
    }
}
