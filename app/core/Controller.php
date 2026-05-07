<?php
namespace App\Core;
use App\Helpers\SensitiveDataHelper;

abstract class Controller
{
    protected $request;
    protected $view;

    public function __construct($request = [], $view = null)
    {
        $this->request = $request;
        $this->view = $view ?? new View();
    }

    protected function render($view, $data = [], $layout = null)
    {
        return $this->view->render($view, $data, $layout);
    }

    protected function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    protected function input($key = null, $default = null)
    {
        $input = array_merge($_GET, $_POST);

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    protected function body()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $payload = json_decode((string) file_get_contents('php://input'), true);

            return is_array($payload) ? $payload : [];
        }

        return $_POST;
    }

    protected function request()
    {
        return $this->request;
    }

    protected function wants_json()
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $path = $this->request()['path'] ?? '';

        return str_contains($accept, 'application/json')
            || str_starts_with($path, '/api')
            || $this->input('format') === 'json';
    }

    protected function require_fields($data, $fields)
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        return $errors;
    }

    protected function sanitize($data)
    {
        return SensitiveDataHelper::strip($data);
    }
}