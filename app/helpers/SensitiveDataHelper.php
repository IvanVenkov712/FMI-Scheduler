<?php
namespace App\Helpers;

class SensitiveDataHelper
{
    private const REMOVED_KEYS = [
        'access_key',
        'access_password',
    ];

    private const REDACTED_KEYS = [
        'access_key',
        'access_password',
    ];

    public static function strip($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array($key, self::REMOVED_KEYS, true)) {
                continue;
            }

            $sanitized[$key] = self::strip($item);
        }

        return $sanitized;
    }

    public static function redact($value, $mask = '[REDACTED]')
    {
        if (!is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array($key, self::REDACTED_KEYS, true)) {
                $sanitized[$key] = $mask;
                continue;
            }

            $sanitized[$key] = self::redact($item, $mask);
        }

        return $sanitized;
    }
}