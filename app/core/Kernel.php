<?php
namespace App\Core;

use App\Helpers\SessionHelper;
use App\Logger\Logger;
use Throwable;

/** Boots configuration, error handling and sessions, then dispatches registered routes. */
class Kernel
{
    private $root;
    private $router;

    public function __construct($root, Router $router)
    {
        $this->root = $root;
        $this->router = $router;
    }

    public static function boot($root)
    {
        self::configure_error_reporting();
        Config::load($root . '/config/app.ini');
        Config::load($root . '/.env');
        ErrorHandler::register();

        $session = new SessionHelper();
        $session->start();

        return new self($root, new Router(self::load_routes($root)));
    }

    public function handle()
    {
        $method = Request::method();
        $uri = Request::path();
        $route = $this->router->match($method, $uri);

        if ($route === null) {
            ErrorHandler::render(404, 'Not Found');
        }

        $this->dispatch($method, $uri, $route);
    }

    private static function configure_error_reporting()
    {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);
    }

    private static function load_routes($root)
    {
        return require $root . '/config/routes.php';
    }

    private function dispatch($method, $uri, $route)
    {
        $controller_name = $route['controller'];
        $method_name = $route['action'];
        $params = $route['params'];
        $controller_class = 'App\\Controllers\\' . $controller_name;
        $request = Request::capture($params);
        $request['method'] = $method;
        $request['path'] = $uri;
        $request['controller'] = $controller_name;
        $request['action'] = $method_name;

        try {
            Logger::operation('Request started', Request::context([
                'controller' => $controller_name,
                'action' => $method_name,
                'params' => $params,
                'query' => $_GET,
                'body' => $_POST,
            ]));

            $controller = new $controller_class($request);
            if (!method_exists($controller, $method_name)) {
                ErrorHandler::render(404, 'Not Found');
            }

            $result = $controller->$method_name(...array_values($params));
            $this->render_result($result);

            Logger::operation('Request completed', Request::context([
                'controller' => $controller_name,
                'action' => $method_name,
                'status' => http_response_code(),
            ]));
        } catch (Throwable $exception) {
            ErrorHandler::render(
                500,
                'Something went wrong while processing your request. Please try again.',
                $exception
            );
        }
    }

    private function render_result($result)
    {
        if (is_string($result)) {
            echo $result;
            return;
        }

        if (!is_array($result)) {
            return;
        }

        if (!Request::wants_json()) {
            ErrorHandler::render(500, 'Something went wrong while processing your request. Please try again.');
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
