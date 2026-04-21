<?php

declare(strict_types=1);

abstract class Controller
{
    protected array $request;
    protected View $view;

    public function __construct(array $request = [])
    {
        $this->request = $request;
        $this->view = new View();
    }

    protected function render(string $view, array $data = [], ?string $layout = null): string
    {
        return $this->view->render($view, $data, $layout);
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    protected function input(?string $key = null, mixed $default = null): mixed
    {
        $input = array_merge($_GET, $_POST);

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    protected function body(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $payload = json_decode((string) file_get_contents('php://input'), true);

            return is_array($payload) ? $payload : [];
        }

        return $_POST;
    }

    protected function request(): array
    {
        return $this->request;
    }

    protected function requireFields(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        return $errors;
    }

    protected function database(): Database
    {
        return Database::getInstance(Database::configFromFile());
    }

    protected function sanitize(mixed $data): mixed
    {
        return SensitiveData::strip($data);
    }
}
