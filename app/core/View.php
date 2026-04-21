<?php

declare(strict_types=1);

class View
{
    private string $viewsPath;
    private ?string $layout;

    public function __construct(?string $viewsPath = null, ?string $layout = null)
    {
        $this->viewsPath = $viewsPath ?? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';
        $this->layout = $layout;
    }

    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = $this->injectCsrfFields($this->renderFile($this->resolveViewPath($view), $data));
        $layout ??= $this->layout;

        if ($layout === null) {
            return $content;
        }

        return $this->injectCsrfFields($this->renderFile($this->resolveViewPath($layout), array_merge($data, [
            'content' => $content,
        ])));
    }

    public function exists(string $view): bool
    {
        return is_file($this->resolveViewPath($view));
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::escape(SessionHelper::csrfToken()) . '">';
    }

    private function renderFile(string $path, array $data): string
    {
        if (!is_file($path)) {
            throw new RuntimeException("View file not found: {$path}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }

    private function resolveViewPath(string $view): string
    {
        $view = trim($view, '/\\');
        $view = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $view);

        if (!str_ends_with($view, '.php')) {
            $view .= '.php';
        }

        return $this->viewsPath . DIRECTORY_SEPARATOR . $view;
    }

    private function injectCsrfFields(string $html): string
    {
        if ($html === '' || stripos($html, '<form') === false) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<form\b(?P<attrs>[^>]*)>(?P<body>.*?)<\/form>/is',
            static function (array $matches): string {
                $form = $matches[0];
                $attrs = $matches['attrs'] ?? '';
                $body = $matches['body'] ?? '';

                if (!preg_match('/\bmethod\s*=\s*([\'"]?)(?<method>[a-zA-Z]+)\1/i', $attrs, $methodMatch)) {
                    return $form;
                }

                if (strtolower((string) ($methodMatch['method'] ?? 'get')) !== 'post') {
                    return $form;
                }

                if (stripos($body, 'name="_csrf_token"') !== false || stripos($body, "name='_csrf_token'") !== false) {
                    return $form;
                }

                return '<form' . $attrs . '>' . View::csrfField() . $body . '</form>';
            },
            $html
        );
    }
}
