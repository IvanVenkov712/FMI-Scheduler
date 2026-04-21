<?php

declare(strict_types=1);

class CsrfMiddleware
{
    public function handle(array $request): bool
    {
        if (!$this->shouldProtect($request)) {
            return true;
        }

        if (SessionHelper::verifyCsrfToken($this->tokenFromRequest())) {
            return true;
        }

        $this->deny($request, 419, 'Invalid or missing CSRF token.');
    }

    private function shouldProtect(array $request): bool
    {
        $method = strtoupper((string) ($request['method'] ?? 'GET'));

        return !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function tokenFromRequest(): ?string
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_XSRF_TOKEN'] ?? null;
        if (is_string($header) && $header !== '') {
            return $header;
        }

        $token = $_POST['_csrf_token'] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function deny(array $request, int $statusCode, string $message): never
    {
        if ($this->expectsJson($request)) {
            Response::error($message, $statusCode);
            exit;
        }

        http_response_code($statusCode);

        $view = new View();
        $errorView = 'errors/' . $statusCode;

        echo $view->exists($errorView)
            ? $view->render($errorView, ['message' => $message])
            : $message;

        exit;
    }

    private function expectsJson(array $request): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $path = (string) ($request['path'] ?? '');

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || str_starts_with($path, '/api');
    }
}
