<?php
namespace App\Helpers;

class SessionHelper
{
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
}
