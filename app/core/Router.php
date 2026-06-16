<?php
namespace App\Core;

class Router
{
    private $routes;

    public function __construct($routes)
    {
        $this->routes = $routes;
    }

    public function match($method, $uri)
    {
        foreach ($this->routes as $pattern => $action) {
            [$route_method, $route_path] = explode(' ', $pattern, 2);
            $route_method = trim($route_method);
            $route_path = trim($route_path);

            if ($route_method !== $method) {
                continue;
            }

            if (preg_match($this->route_regex($route_path), $uri, $matches)) {
                return [
                    'controller' => $action[0],
                    'action' => $action[1],
                    'params' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY),
                ];
            }
        }

        return null;
    }

    private function route_regex($route_path)
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route_path);

        return '#^' . $regex . '$#';
    }
}
