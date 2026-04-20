<?php

declare(strict_types=1);

class App
{
    private string $rootPath;
    private array $config;
    private Router $router;

    public function __construct(?string $rootPath = null)
    {
        $this->rootPath = $rootPath ?? dirname(__DIR__, 2);
        $this->config = $this->loadConfig('app');

        $this->registerAutoloader();
        $this->registerErrorHandling();
        $this->startSession();

        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Router.php';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'View.php';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Controller.php';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Database.php';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Model.php';

        Database::getInstance(Database::configFromFile($this->path('config/database.php')));

        $this->router = new Router();
        $this->registerDefaultMiddleware();
        $this->loadRoutes();
    }

    public function run(): mixed
    {
        return $this->router->dispatch();
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? $default;
    }

    public function path(string $path = ''): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return rtrim($this->rootPath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
    }

    private function registerAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            $class = ltrim($class, '\\');
            $relativeClass = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

            $directories = [
                'app/core',
                'app/controllers',
                'app/models',
                'app/services',
                'app/repositories',
                'app/middleware',
                'app/helpers',
            ];

            foreach ($directories as $directory) {
                $path = $this->path($directory . DIRECTORY_SEPARATOR . basename($relativeClass));

                if (is_file($path)) {
                    require_once $path;
                    return;
                }
            }
        });
    }

    private function registerErrorHandling(): void
    {
        $debug = (bool) ($this->config['debug'] ?? true);

        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);

        set_exception_handler(function (Throwable $exception) use ($debug): void {
            http_response_code(500);
            error_log((string) $exception);

            if ($debug) {
                echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
                return;
            }

            $view = new View();
            echo $view->exists('errors/500')
                ? $view->render('errors/500')
                : 'Internal server error.';
        });
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $savePath = session_save_path();
            $fallbackPath = $this->path('storage/temp');

            if (($savePath === '' || !is_dir($savePath) || !is_writable($savePath)) && is_dir($fallbackPath) && is_writable($fallbackPath)) {
                session_save_path($fallbackPath);
            }

            session_start();
        }
    }

    private function loadConfig(string $name): array
    {
        $path = $this->path('config/' . $name . '.php');

        if (!is_file($path)) {
            return [];
        }

        $config = require $path;

        return is_array($config) ? $config : [];
    }

    private function loadRoutes(): void
    {
        foreach (['config/routes.php', 'routes/web.php', 'routes/api.php'] as $routeFile) {
            $path = $this->path($routeFile);

            if (is_file($path)) {
                $router = $this->router;
                require $path;
            }
        }

        if ($this->routerIsEmpty()) {
            $this->router->get('/', static fn (): string => 'Schedule management system');
        }
    }

    private function registerDefaultMiddleware(): void
    {
        $this->router->middleware('auth', 'AuthMiddleware');
        $this->router->middleware('admin', 'AdminMiddleware');
        $this->router->middleware('role', 'RoleMiddleware');
    }

    private function routerIsEmpty(): bool
    {
        $reflection = new ReflectionClass($this->router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);

        return $property->getValue($this->router) === [];
    }
}
