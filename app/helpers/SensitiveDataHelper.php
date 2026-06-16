<?php
namespace App\Helpers;

class SensitiveDataHelper
{
    private const REMOVED_KEYS = [
        'access_key',
        'access_password',
        'password',
        'password_hash',
        'password_confirmation',
        'token',
        'csrf_token',
        '_token',
        'secret',
    ];

    private const REDACTED_KEYS = [
        'access_key',
        'access_password',
        'password',
        'password_hash',
        'password_confirmation',
        'token',
        'csrf_token',
        '_token',
        'secret',
    ];

    public static function strip($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && self::matches($key, self::REMOVED_KEYS)) {
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
            if (is_string($key) && self::matches($key, self::REDACTED_KEYS)) {
                $sanitized[$key] = $mask;
                continue;
            }

            $sanitized[$key] = self::redact($item, $mask);
        }

        return $sanitized;
    }

    private static function matches($key, $keys)
    {
        $key = strtolower($key);

        foreach ($keys as $candidate) {
            if ($key === $candidate || str_contains($key, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
