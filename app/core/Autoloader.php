<?php
namespace App\Core;

/** Maps App namespaces to app/ files, tolerating directory case differences. */
class Autoloader
{
    public static function register($app_path)
    {
        spl_autoload_register(function ($class) use ($app_path) {
            $prefix = 'App\\';

            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = rtrim($app_path, '/\\') . '/' . $relative . '.php';

            if (is_file($file)) {
                require $file;
                return;
            }

            $case_insensitive = self::resolve_case_insensitive_path($app_path, $relative . '.php');
            if ($case_insensitive !== null) {
                require $case_insensitive;
            }
        });
    }

    private static function resolve_case_insensitive_path($base_dir, $relative_path)
    {
        $path = rtrim($base_dir, '/\\');
        $parts = preg_split('#[\\\\/]#', $relative_path);

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $next = $path . DIRECTORY_SEPARATOR . $part;
            if (file_exists($next)) {
                $path = $next;
                continue;
            }

            $matched = self::find_case_insensitive_match($path, $part);
            if ($matched === null) {
                return null;
            }

            $path = $matched;
        }

        return is_file($path) ? $path : null;
    }

    private static function find_case_insensitive_match($path, $part)
    {
        $matches = glob($path . DIRECTORY_SEPARATOR . '*') ?: [];

        foreach ($matches as $candidate) {
            if (strcasecmp(basename($candidate), $part) === 0) {
                return $candidate;
            }
        }

        return null;
    }
}
