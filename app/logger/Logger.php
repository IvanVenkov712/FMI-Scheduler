<?php
namespace App\Logger;

use App\Core\Config;
use Throwable;

class Logger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_hash',
        'password_confirmation',
        'token',
        'csrf_token',
        '_token',
        'secret',
        'access_key',
        'access_password',
    ];

    private const GDPR_KEYS = [
        'email',
        'egn',
        'faculty_number',
        'first_name',
        'last_name',
        'full_name',
        'ip',
        'name',
        'phone',
        'user_id',
        'username',
    ];

    public static function operation(string $message, array $context = []): void
    {
        self::info($message, $context + ['type' => 'operation']);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function exception(Throwable $exception, array $context = []): void
    {
        self::error($exception->getMessage(), $context + [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s%s%s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            empty($context) ? '' : ' ' . self::encode_context(self::sanitize($context)),
            PHP_EOL
        );

        $file = self::log_file();
        $directory = dirname($file);

        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log($line);
        }
    }

    private static function sanitize($value)
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                if (is_string($key) && self::is_sensitive_key($key)) {
                    $sanitized[$key] = '[REDACTED]';
                    continue;
                }

                if (self::gdpr_logging_enabled() && is_string($key) && self::is_gdpr_key($key)) {
                    $sanitized[$key] = '[GDPR_REDACTED]';
                    continue;
                }

                $sanitized[$key] = self::sanitize($item);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return method_exists($value, 'to_array')
                ? self::sanitize($value->to_array())
                : self::sanitize(get_object_vars($value));
        }

        if (is_resource($value)) {
            return '[RESOURCE]';
        }

        return $value;
    }

    private static function is_sensitive_key(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($key === $sensitiveKey || str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private static function is_gdpr_key(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::GDPR_KEYS as $gdprKey) {
            if ($key === $gdprKey || str_contains($key, $gdprKey)) {
                return true;
            }
        }

        return false;
    }

    private static function gdpr_logging_enabled(): bool
    {
        return strcasecmp((string) Config::get('LOG_LEVEL', 'Normal'), 'GDPR') === 0;
    }

    private static function encode_context(array $context): string
    {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? '[unserializable context]' : $json;
    }

    private static function log_file(): string
    {
        $root = defined('ROOT') ? ROOT : dirname(__DIR__, 2);

        return $root . '/storage/logs/app.log';
    }
}
