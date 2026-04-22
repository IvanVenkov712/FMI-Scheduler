<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];
    private array $middlewareAliases = [];

    public function get(string $path, mixed $action, array|string $middleware = []): self
    {
        return $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, mixed $action, array|string $middleware = []): self
    {
        return $this->add('POST', $path, $action, $middleware);
    }

    public function put(string $path, mixed $action, array|string $middleware = []): self
    {
        return $this->add('PUT', $path, $action, $middleware);
    }

    public function patch(string $path, mixed $action, array|string $middleware = []): self
    {
        return $this->add('PATCH', $path, $action, $middleware);
    }

    public function delete(string $path, mixed $action, array|string $middleware = []): self
    {
        return $this->add('DELETE', $path, $action, $middleware);
    }

    public function any(string $path, mixed $action, array|string $middleware = []): self
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->add($method, $path, $action, $middleware);
        }

        return $this;
    }

    public function add(string $method, string $path, mixed $action, array|string $middleware = []): self
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        $this->routes[$method][] = [
            'path' => $path,
            'pattern' => $this->compilePattern($path),
            'action' => $action,
            'middleware' => (array) $middleware,
        ];

        return $this;
    }

    public function middleware(string $alias, string|callable $middleware): self
    {
        $this->middlewareAliases[$alias] = $middleware;

        return $this;
    }

    public function dispatch(?string $method = null, ?string $uri = null): mixed
    {
        $method = $this->resolveMethod($method);
        $path = $this->normalizePath(parse_url($uri ?? ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (string|int $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            $request = [
                'method' => $method,
                'path' => $path,
                'params' => $params,
                'query' => $_GET,
                'body' => $_POST,
            ];

            $this->runGlobalMiddleware($request);
            $this->runMiddleware($route['middleware'], $request);

            return $this->send($this->callAction($route['action'], $request, $params));
        }

        return $this->abort(404, 'Page not found.');
    }

    public function abort(int $statusCode, string $message): never
    {
        http_response_code($statusCode);

        $view = new View();
        $errorView = 'errors/' . $statusCode;

        if ($view->exists($errorView)) {
            echo $view->render($errorView, ['message' => $message]);
        } else {
            echo $message;
        }

        exit;
    }

    private function callAction(mixed $action, array $request, array $params): mixed
    {
        if ($action instanceof Closure) {
            return $action($request, ...array_values($params));
        }

        if (is_string($action)) {
            $action = str_contains($action, '@') ? explode('@', $action, 2) : [$action, 'index'];
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;
            $instance = is_string($controller) ? new $controller($request) : $controller;

            if (!method_exists($instance, $method)) {
                throw new BadMethodCallException("Controller method not found: {$method}");
            }

            return $instance->{$method}(...array_values($params));
        }

        if (is_callable($action)) {
            return $action($request, ...array_values($params));
        }

        throw new InvalidArgumentException('Invalid route action.');
    }

    private function runMiddleware(array $middleware, array $request): void
    {
        foreach ($middleware as $entry) {
            $handler = is_string($entry) && array_key_exists($entry, $this->middlewareAliases)
                ? $this->middlewareAliases[$entry]
                : $entry;

            if (is_string($handler) && class_exists($handler)) {
                $handler = [new $handler(), 'handle'];
            }

            if (!is_callable($handler)) {
                $label = is_string($entry)
                    ? $entry
                    : (is_array($entry) ? 'array middleware' : gettype($entry));

                throw new RuntimeException("Middleware is not callable: {$label}");
            }

            $result = $handler($request);

            if ($result === false) {
                $this->abort(403, 'Access denied.');
            }
        }
    }

    private function runGlobalMiddleware(array $request): void
    {
        if (!class_exists('CsrfMiddleware')) {
            return;
        }

        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $middleware = new CsrfMiddleware();
        $middleware->handle($request);
    }

    private function send(mixed $response): mixed
    {
        if (is_array($response)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $response;
        }

        if ($response !== null) {
            echo (string) $response;
        }

        return $response;
    }

    private function resolveMethod(?string $method): string
    {
        $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        return $method;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);

        return '#^' . $pattern . '$#';
    }
}
