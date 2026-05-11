<?php

define('ROOT', __DIR__);

spl_autoload_register(function ($class) {
    $map = ['App\\' => ROOT . '/app/'];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $dir . $relative . '.php';
            if (is_file($file)) { require $file; return; }
            $lower = $dir . strtolower($relative) . '.php';
            if (is_file($lower)) { require $lower; return; }
        }
    }
});

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
    'POST /rooms/{id}/availability' => ['RoomController', 'check_availability'],
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
    'GET  /requests/create'         => ['RequestController', 'create_form'],
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

    'GET  /import-export/import'    => ['ImportExportController', 'import_form'],
    'GET  /import-export/export'    => ['ImportExportController', 'export_form'],
    'POST /import-export/import-csv'  => ['ImportExportController', 'import_csv'],
    'POST /import-export/import-xml'  => ['ImportExportController', 'import_xml'],
    'POST /import-export/import-ascii-plan' => ['ImportExportController', 'import_ascii_plan'],
    'GET  /import-export/export-schedule/{id}' => ['ImportExportController', 'export_schedule'],

    'GET  /users'                   => ['UserController', 'index'],
    'GET  /users/profile'           => ['UserController', 'profile'],
    'GET  /users/{id}'              => ['UserController', 'show'],

    'GET  /constraints'             => ['MovementConstraintController', 'index'],
    'POST /constraints'             => ['MovementConstraintController', 'store'],

    'GET  /exam-sessions/create'    => ['ExamSessionController', 'create_form'],
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
    http_response_code(404);
    echo '404 Not Found';
    exit;
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

$controller = new $controllerClass($request);
$result = $controller->$methodName(...array_values($params));

if (is_string($result)) {
    echo $result;
} elseif (is_array($result)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
