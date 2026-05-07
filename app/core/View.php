<?php
namespace App\Core;
use \RuntimeException;

class View
{
    private $viewsPath;
    private $layout;

    public function __construct($viewsPath = null, $layout = null)
    {
        $this->viewsPath = $viewsPath ?? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';
        $this->layout = $layout;
    }

    public function render($view, $data = [], $layout = null)
    {
        $content = $this->render_file($this->resolve_view_path($view), $data);
        $layout ??= $this->layout;

        if ($layout === null) {
            return $content;
        }

        return $this->render_file($this->resolve_view_path($layout), array_merge($data, [
            'content' => $content,
        ]));
    }

    public function exists($view)
    {
        return is_file($this->resolve_view_path($view));
    }

    public static function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function render_file($path, $data)
    {
        if (!is_file($path)) {
            throw new RuntimeException("View file not found: {$path}");
        }

        $shared_ui = dirname($this->viewsPath) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';
        if (is_file($shared_ui)) {
            require_once $shared_ui;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }

    private function resolve_view_path(string $view): string
    {
        $view = trim($view, '/\\');
        $view = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $view);

        if (!str_ends_with($view, '.php')) {
            $view .= '.php';
        }

        return $this->viewsPath . DIRECTORY_SEPARATOR . $view;
    }
}