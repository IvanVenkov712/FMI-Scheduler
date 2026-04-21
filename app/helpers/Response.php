<?php

declare(strict_types=1);

class Response
{
    public static function json(array $data, int $statusCode = 200, array $headers = []): array
    {
        self::setStatus($statusCode);
        self::header('Content-Type', 'application/json; charset=utf-8');
        self::sendHeaders($headers);

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Unable to encode JSON response.');
        }

        echo $json;

        return $data;
    }

    public static function success(array $data = [], string $message = 'OK', int $statusCode = 200): array
    {
        return self::json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): array
    {
        return self::json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    public static function redirect(string $url, int $statusCode = 302): never
    {
        self::setStatus($statusCode);
        self::header('Location', $url);
        exit;
    }

    public static function noContent(int $statusCode = 204): null
    {
        self::setStatus($statusCode);
        return null;
    }

    public static function download(string $path, ?string $downloadName = null, string $contentType = 'application/octet-stream'): never
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Download file not found or not readable.');
        }

        $name = $downloadName ?? basename($path);

        self::setStatus(200);
        self::header('Content-Type', $contentType);
        self::header('Content-Length', (string) filesize($path));
        self::header('Content-Disposition', 'attachment; filename="' . addslashes($name) . '"');

        readfile($path);
        exit;
    }

    private static function setStatus(int $statusCode): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
        }
    }

    private static function header(string $name, string $value): void
    {
        if (!headers_sent()) {
            header($name . ': ' . $value);
        }
    }

    private static function sendHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            self::header((string) $name, (string) $value);
        }
    }
}
