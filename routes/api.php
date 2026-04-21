<?php

declare(strict_types=1);

/** @var Router $router */

$auth = ['auth'];
$admin = ['admin'];
$scheduleManagers = [[RoleMiddleware::forRoles([ROLE_SCHEDULE_MANAGER]), 'handle']];
$teachers = [[RoleMiddleware::forRoles([ROLE_TEACHER]), 'handle']];
$technicalStaff = [[RoleMiddleware::forRoles([ROLE_TECHNICAL_STAFF]), 'handle']];
$managersOrTeachers = [[RoleMiddleware::forRoles([ROLE_SCHEDULE_MANAGER, ROLE_TEACHER]), 'handle']];

$router->get('/api/dashboard', 'DashboardController@index', $auth);

$router->get('/api/buildings', 'BuildingController@index', $auth);
$router->post('/api/buildings', 'BuildingController@store', $admin);
$router->get('/api/buildings/{id}', 'BuildingController@details', $auth);
$router->put('/api/buildings/{id}', 'BuildingController@update', $admin);
$router->delete('/api/buildings/{id}', 'BuildingController@delete', $admin);

$router->get('/api/floors', 'FloorController@index', $auth);
$router->post('/api/floors', 'FloorController@store', $admin);
$router->get('/api/floors/{id}/plan', 'FloorController@plan', $auth);
$router->put('/api/floors/{id}/plan', 'FloorController@updatePlan', $admin);

$router->get('/api/rooms', 'RoomController@index', $auth);
$router->post('/api/rooms', 'RoomController@store', array_merge($auth, $technicalStaff));
$router->get('/api/rooms/search', 'RoomController@searchAvailable', $auth);
$router->get('/api/rooms/{id}/availability', 'RoomController@availability', $auth);

$router->get('/api/schedules', 'ScheduleController@index', $auth);
$router->post('/api/schedules', 'ScheduleController@store', array_merge($auth, $scheduleManagers));
$router->get('/api/schedules/{id}/calendar', 'ScheduleController@calendar', $auth);
$router->post('/api/schedules/{id}/events', 'ScheduleController@attachEvent', array_merge($auth, $scheduleManagers));

$router->get('/api/events', 'EventController@index', array_merge($auth, $scheduleManagers));
$router->post('/api/events', 'EventController@store', array_merge($auth, $scheduleManagers));
$router->put('/api/events/{id}', 'EventController@update', array_merge($auth, $scheduleManagers));

$router->get('/api/requests', 'RequestController@index', array_merge($auth, $managersOrTeachers));
$router->post('/api/requests', 'RequestController@store', array_merge($auth, $managersOrTeachers));
$router->post('/api/requests/{id}/review', 'RequestController@review', array_merge($auth, $scheduleManagers));

$router->get('/api/resources', 'ResourceController@index', array_merge($auth, $technicalStaff));
$router->post('/api/resources', 'ResourceController@store', array_merge($auth, $technicalStaff));
$router->get('/api/resources/room/{roomId}', 'ResourceController@roomResources', $auth);

$router->get('/api/routes', 'RouteController@index', $auth);
$router->post('/api/routes', 'RouteController@store', $admin);
$router->get('/api/routes/{id}/steps', 'RouteController@steps', $auth);
$router->post('/api/routes/{id}/steps', 'RouteController@saveSteps', $admin);

$router->get('/api/allocation/search', 'AllocationController@search', $auth);
$router->post('/api/allocation/auto-assign', 'AllocationController@autoAssign', array_merge($auth, $scheduleManagers));
$router->post('/api/allocation/auto-assign/{scheduleId}', 'AllocationController@autoAssign', array_merge($auth, $scheduleManagers));
$router->get('/api/allocation/result/{id}', 'AllocationController@result', array_merge($auth, $scheduleManagers));

$router->post('/api/import', 'ImportExportController@import', $admin);
$router->get('/api/export', 'ImportExportController@export', $admin);
