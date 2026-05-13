<?php

define('ROOT', __DIR__);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

spl_autoload_register(function ($class) {
    $map = ['App\\' => ROOT . '/app/'];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $dir . $relative . '.php';
            if (is_file($file)) { require $file; return; }

            $caseInsensitive = resolve_case_insensitive_path($dir, $relative . '.php');
            if ($caseInsensitive !== null) { require $caseInsensitive; return; }
        }
    }
});

set_exception_handler(function (Throwable $exception) {
    render_error_response(500, 'Internal Server Error', $exception);
});

function resolve_case_insensitive_path(string $baseDir, string $relativePath): ?string
{
    $path = rtrim($baseDir, '/\\');
    $parts = preg_split('#[\\\\/]#', $relativePath);

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        $next = $path . DIRECTORY_SEPARATOR . $part;
        if (file_exists($next)) {
            $path = $next;
            continue;
        }

        $matches = glob($path . DIRECTORY_SEPARATOR . '*') ?: [];
        $matched = null;

        foreach ($matches as $candidate) {
            if (strcasecmp(basename($candidate), $part) === 0) {
                $matched = $candidate;
                break;
            }
        }

        if ($matched === null) {
            return null;
        }

        $path = $matched;
    }

    return is_file($path) ? $path : null;
}

function request_wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    return str_contains($accept, 'application/json')
        || strcasecmp($requestedWith, 'XMLHttpRequest') === 0
        || str_starts_with($path, '/api')
        || ($_GET['format'] ?? null) === 'json';
}

function render_error_response(int $status, string $message, ?Throwable $exception = null): void
{
    if ($exception !== null) {
        error_log((string) $exception);
    }

    if (!headers_sent()) {
        http_response_code($status);
    }

    if (request_wants_json()) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $view = new App\Core\View();
    $errorView = $status === 404 ? 'errors/404' : 'errors/500';

    if ($view->exists($errorView)) {
        echo $view->render($errorView, ['message' => $message]);
    } else {
        echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    exit;
}

$session = new App\Helpers\SessionHelper();
$session->start();

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base)) ?: '/';
}
$uri = '/' . ltrim($uri, '/');

if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

$routes = [
    'GET  /'                        => ['DashboardController', 'index'],
    'GET  /dashboard'               => ['DashboardController', 'index'],
    'GET  /login'                   => ['AuthController', 'login_form'],
    'POST /login'                   => ['AuthController', 'login'],
    'POST /logout'                  => ['AuthController', 'logout'],
    'GET  /register'                => ['AuthController', 'register_form'],
    'POST /register'                => ['AuthController', 'register'],
    'GET  /auth/me'                 => ['AuthController', 'me'],

    'GET  /buildings'               => ['BuildingController', 'index'],
    'GET  /buildings/create'        => ['BuildingController', 'create'],
    'POST /buildings'               => ['BuildingController', 'store'],
    'GET  /buildings/{id}'          => ['BuildingController', 'show'],
    'GET  /buildings/{id}/edit'     => ['BuildingController', 'edit'],
    'PUT  /buildings/{id}'          => ['BuildingController', 'update'],
    'POST /buildings/{id}/update'   => ['BuildingController', 'update'],
    'DELETE /buildings/{id}'        => ['BuildingController', 'destroy'],
    'POST /buildings/{id}/delete'   => ['BuildingController', 'destroy'],
    'GET  /buildings/{id}/floors'   => ['BuildingController', 'floors'],

    'GET  /floors'                  => ['FloorController', 'index'],
    'GET  /floors/create'           => ['FloorController', 'create'],
    'POST /floors'                  => ['FloorController', 'store'],
    'GET  /floors/{id}'             => ['FloorController', 'show'],
    'GET  /floors/{id}/edit'        => ['FloorController', 'edit'],
    'PUT  /floors/{id}'             => ['FloorController', 'update'],
    'POST /floors/{id}/update'      => ['FloorController', 'update'],
    'DELETE /floors/{id}'           => ['FloorController', 'destroy'],
    'POST /floors/{id}/delete'      => ['FloorController', 'destroy'],
    'GET  /floors/{id}/rooms'       => ['FloorController', 'rooms'],
    'POST /floors/{id}/import-ascii-plan' => ['FloorController', 'import_ascii_plan'],
    'GET  /floors/{id}/export-ascii-plan' => ['FloorController', 'export_ascii_plan'],

    'GET  /rooms'                   => ['RoomController', 'index'],
    'GET  /rooms/create'            => ['RoomController', 'create'],
    'POST /rooms'                   => ['RoomController', 'store'],
    'GET  /rooms/{id}'              => ['RoomController', 'show'],
    'GET  /rooms/{id}/edit'         => ['RoomController', 'edit'],
    'PUT  /rooms/{id}'              => ['RoomController', 'update'],
    'POST /rooms/{id}/update'       => ['RoomController', 'update'],
    'DELETE /rooms/{id}'            => ['RoomController', 'destroy'],
    'POST /rooms/{id}/delete'       => ['RoomController', 'destroy'],
    'POST /rooms/{id}/availability' => ['RoomController', 'availability'],
    'GET  /rooms/{id}/resources'    => ['RoomController', 'resources'],
    'GET  /rooms/{id}/events'       => ['RoomController', 'events'],

    'GET  /schedules'               => ['ScheduleController', 'index'],
    'GET  /schedules/create'        => ['ScheduleController', 'create'],
    'POST /schedules'               => ['ScheduleController', 'store'],
    'GET  /schedules/{id}'          => ['ScheduleController', 'show'],
    'GET  /schedules/{id}/calendar' => ['ScheduleController', 'calendar'],
    'PUT  /schedules/{id}'          => ['ScheduleController', 'update'],
    'POST /schedules/{id}/update'   => ['ScheduleController', 'update'],
    'DELETE /schedules/{id}'        => ['ScheduleController', 'destroy'],
    'POST /schedules/{id}/delete'   => ['ScheduleController', 'destroy'],
    'POST /schedules/{id}/publish-event' => ['ScheduleController', 'publish_event'],
    'POST /schedules/{id}/remove-event'  => ['ScheduleController', 'remove_event'],

    'GET  /events'                  => ['EventController', 'index'],
    'GET  /events/create'           => ['EventController', 'create'],
    'POST /events'                  => ['EventController', 'store'],
    'GET  /events/{id}'             => ['EventController', 'show'],
    'GET  /events/{id}/edit'        => ['EventController', 'edit'],
    'PUT  /events/{id}'             => ['EventController', 'update'],
    'POST /events/{id}/update'      => ['EventController', 'update'],
    'DELETE /events/{id}'           => ['EventController', 'destroy'],
    'POST /events/{id}/delete'      => ['EventController', 'destroy'],
    'POST /events/{id}/approve'     => ['EventController', 'approve'],
    'POST /events/{id}/reject'      => ['EventController', 'reject'],
    'POST /events/submit-request'   => ['EventController', 'submit_request'],

    'GET  /requests'                => ['RequestController', 'index'],
    'GET  /requests/create'         => ['RequestController', 'create'],
    'POST /requests'                => ['RequestController', 'store'],
    'GET  /requests/{id}/review'    => ['RequestController', 'review'],

    'GET  /resources'               => ['ResourceController', 'index'],
    'POST /resources'               => ['ResourceController', 'store'],

    'GET  /routes'                  => ['RouteController', 'index'],
    'GET  /routes/map'              => ['RouteController', 'map'],
    'POST /routes'                  => ['RouteController', 'store'],
    'GET  /routes/{id}'             => ['RouteController', 'show'],
    'POST /routes/find'             => ['RouteController', 'find'],

    'GET  /allocation/search'       => ['AllocationController', 'search_form'],
    'POST /allocation/search'       => ['AllocationController', 'search'],
    'POST /allocation/{id}/assign'  => ['AllocationController', 'assign'],

    'GET  /import-export/import'    => ['ImportExportController', 'import'],
    'GET  /import-export/export'    => ['ImportExportController', 'export'],
    'POST /import-export/import-csv'  => ['ImportExportController', 'import_csv'],
    'POST /import-export/import-xml'  => ['ImportExportController', 'import_xml'],
    'POST /import-export/import-ascii-plan' => ['ImportExportController', 'import_ascii_plan'],
    'GET  /import-export/export-floor/{id}' => ['ImportExportController', 'export_floor'],
    'GET  /import-export/export-building/{id}' => ['ImportExportController', 'export_building'],
    'GET  /import-export/export-schedule/{id}' => ['ImportExportController', 'export_schedule'],
    'GET  /import-export/export-xml' => ['ImportExportController', 'export_xml'],

    'GET  /users'                   => ['UserController', 'index'],
    'GET  /users/profile'           => ['UserController', 'profile'],
    'GET  /users/{id}'              => ['UserController', 'show'],

    'GET  /constraints'             => ['MovementConstraintController', 'index'],
    'POST /constraints'             => ['MovementConstraintController', 'store'],

    'GET  /exam-sessions/create'    => ['ExamSessionController', 'create'],
    'POST /exam-sessions'           => ['ExamSessionController', 'store'],
];

$matched = null;
$params = [];

foreach ($routes as $pattern => $action) {
    [$routeMethod, $routePath] = explode(' ', $pattern, 2);
    $routeMethod = trim($routeMethod);
    $routePath = trim($routePath);

    if ($routeMethod !== $method) continue;

    $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $routePath);
    if (preg_match('#^' . $regex . '$#', $uri, $m)) {
        $matched = $action;
        $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
        break;
    }
}

if (!$matched) {
    render_error_response(404, 'Not Found');
}

[$controllerName, $methodName] = $matched;
$controllerClass = 'App\\Controllers\\' . $controllerName;

$request = [
    'method' => $method,
    'path' => $uri,
    'params' => $params,
    'query' => $_GET,
    'body' => $_POST,
];

try {
    $controller = new $controllerClass($request);
    if (!method_exists($controller, $methodName)) {
        render_error_response(404, 'Not Found');
    }

    $result = $controller->$methodName(...array_values($params));

    if (is_string($result)) {
        echo $result;
    } elseif (is_array($result)) {
        if (request_wants_json()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            render_error_response(500, 'Internal Server Error');
        }
    }
} catch (Throwable $exception) {
    render_error_response(500, 'Internal Server Error', $exception);
}
