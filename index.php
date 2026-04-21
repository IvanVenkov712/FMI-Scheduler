<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);
define('APP_START', microtime(true));

bootstrapRequest();
bootstrapEnvironment(ROOT_PATH);

$appFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'App.php';

if (!is_file($appFile)) {
    failEarly('Application core was not found.', 500);
}

require_once $appFile;

try {
    $app = new App(ROOT_PATH);
    $app->run();
} catch (Throwable $exception) {
    failEarly($exception->getMessage(), 500, $exception);
}

function bootstrapEnvironment(string $rootPath): void
{
    $constantsFile = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'constants.php';
    $configFile = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

    if (is_file($constantsFile)) {
        require_once $constantsFile;
    }

    $config = is_file($configFile) ? require $configFile : [];
    $timezone = $config['timezone'] ?? (defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Europe/Sofia');

    date_default_timezone_set((string) $timezone);

    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding((string) ($config['encoding'] ?? 'UTF-8'));
    }

    $debug = filter_var($config['debug'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $logsPath = $config['paths']['logs'] ?? ($rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs');
    $errorLogFile = $config['logging']['error_file'] ?? ($logsPath . DIRECTORY_SEPARATOR . 'error.log');

    if (is_string($logsPath) && $logsPath !== '' && !is_dir($logsPath)) {
        mkdir($logsPath, 0775, true);
    }

    if (is_string($errorLogFile) && $errorLogFile !== '') {
        ini_set('log_errors', '1');
        ini_set('error_log', $errorLogFile);
    }

    ini_set('display_errors', $debug ? '1' : '0');

    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        $session = $config['session'] ?? [];

        if (!empty($session['name'])) {
            session_name((string) $session['name']);
        }

        if (!empty($session['lifetime_minutes'])) {
            session_set_cookie_params([
                'lifetime' => (int) $session['lifetime_minutes'] * 60,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
}

function bootstrapRequest(): void
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

    $basePath = basePathFromScriptName();
    $normalizedPath = stripBasePath($requestPath, $basePath);
    $query = parse_url($requestUri, PHP_URL_QUERY);

    $_SERVER['REQUEST_URI'] = $normalizedPath . ($query !== null && $query !== '' ? '?' . $query : '');
}

function basePathFromScriptName(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDirectory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    return $scriptDirectory === '' || $scriptDirectory === '.' ? '' : $scriptDirectory;
}

function stripBasePath(string $path, string $basePath): string
{
    $path = '/' . ltrim($path, '/');

    if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath . '/')) {
        $path = substr($path, strlen($basePath));
    } elseif ($basePath !== '' && $path === $basePath) {
        $path = '/';
    }

    return $path === '' ? '/' : $path;
}

function failEarly(string $message, int $statusCode = 500, ?Throwable $exception = null): never
{
    http_response_code($statusCode);

    logBootstrapFailure($message, $statusCode, $exception);

    $debug = loadDebugFlag();

    if ($debug) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $statusCode === 404 ? 'Page not found.' : 'Internal server error.';
        exit;
    }

    echo $statusCode === 404 ? 'Page not found.' : 'Internal server error.';
    exit;
}

function loadDebugFlag(): bool
{
    static $debug = null;

    if (is_bool($debug)) {
        return $debug;
    }

    $configFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
    $config = is_file($configFile) ? require $configFile : [];
    $debug = filter_var($config['debug'] ?? false, FILTER_VALIDATE_BOOLEAN);

    return $debug;
}

function logBootstrapFailure(string $message, int $statusCode, ?Throwable $exception = null): void
{
    $configFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
    $config = is_file($configFile) ? require $configFile : [];
    $logsPath = $config['paths']['logs'] ?? (ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs');
    $errorLogFile = $config['logging']['error_file'] ?? ($logsPath . DIRECTORY_SEPARATOR . 'error.log');

    if (is_string($logsPath) && $logsPath !== '' && !is_dir($logsPath)) {
        mkdir($logsPath, 0775, true);
    }

    $details = $exception
        ? sprintf("%s\n%s", get_class($exception) . ': ' . $exception->getMessage(), $exception->getTraceAsString())
        : $message;

    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $line = sprintf(
        "[%s] %s %s | HTTP %d | %s%s",
        date('Y-m-d H:i:s'),
        $requestMethod,
        $requestUri,
        $statusCode,
        $message,
        $details !== $message ? PHP_EOL . $details : ''
    );

    if (is_string($errorLogFile) && $errorLogFile !== '') {
        error_log($line . PHP_EOL, 3, $errorLogFile);
        return;
    }

    error_log($line);
}
