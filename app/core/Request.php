<?php
namespace App\Core;

use App\Helpers\SessionHelper;

/** Reads HTTP metadata, form method overrides, JSON negotiation and log context. */
class Request
{
    public static function capture($params = [])
    {
        return [
            'method' => self::method(),
            'path' => self::path(),
            'params' => $params,
            'query' => $_GET,
            'body' => $_POST,
        ];
    }

    public static function method()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST' && isset($_POST['_method'])) {
            return strtoupper((string) $_POST['_method']);
        }

        return $method;
    }

    public static function path()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }

        return '/' . ltrim($uri, '/');
    }

    public static function wants_json($path = null)
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $path ??= parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return str_contains($accept, 'application/json')
            || strcasecmp($requested_with, 'XMLHttpRequest') === 0
            || str_starts_with($path, '/api')
            || ($_GET['format'] ?? null) === 'json';
    }

    public static function context($context = [])
    {
        $session = $_SESSION ?? [];
        $user = $session[SessionHelper::USER_KEY] ?? [];
        $username = is_array($user) ? ($user['username'] ?? null) : null;

        return $context + [
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_id' => $session[SessionHelper::USER_ID_KEY] ?? null,
            'username' => $username,
        ];
    }
}
