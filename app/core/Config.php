<?php
namespace App\Core;

/**
 * INI-backed configuration with process-environment precedence.
 * Later loads override earlier files; even an empty getenv() value wins.
 */
class Config
{
    private static $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        $values = parse_ini_file($file, false, INI_SCANNER_TYPED);
        if ($values === false) {
            return;
        }

        self::$values = $values + self::$values;
    }

    public static function get(string $key, $default = null)
    {
        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return array_key_exists($key, self::$values) ? self::$values[$key] : $default;
    }
}
