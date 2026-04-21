<?php

declare(strict_types=1);

/** @var Router $router */

$auth = ['auth'];
$admin = ['admin'];
$scheduleManagers = [[RoleMiddleware::forRoles([ROLE_SCHEDULE_MANAGER]), 'handle']];
$teachers = [[RoleMiddleware::forRoles([ROLE_TEACHER]), 'handle']];
$technicalStaff = [[RoleMiddleware::forRoles([ROLE_TECHNICAL_STAFF]), 'handle']];
$managersOrTeachers = [[RoleMiddleware::forRoles([ROLE_SCHEDULE_MANAGER, ROLE_TEACHER]), 'handle']];

$router->get('/', 'DashboardController@index', $auth);
$router->get('/dashboard', 'DashboardController@index', $auth);

$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@authenticate');
$router->get('/register', 'AuthController@register');
$router->post('/register', 'AuthController@storeRegistration');
$router->post('/logout', 'AuthController@logout', $auth);
$router->get('/logout', 'AuthController@logout', $auth);

$router->get('/buildings', 'BuildingController@index', $auth);
$router->get('/buildings/create', 'BuildingController@create', $admin);
$router->post('/buildings', 'BuildingController@store', $admin);
$router->get('/buildings/{id}', 'BuildingController@details', $auth);
$router->get('/buildings/{id}/edit', 'BuildingController@edit', $admin);
$router->post('/buildings/{id}', 'BuildingController@update', $admin);
$router->put('/buildings/{id}', 'BuildingController@update', $admin);
$router->delete('/buildings/{id}', 'BuildingController@delete', $admin);
$router->get('/buildings/{id}/floors', 'BuildingController@floors', $auth);
$router->get('/buildings/{id}/vertical-view/{orientation}', 'BuildingController@verticalView', $auth);

$router->get('/floors', 'FloorController@index', $auth);
$router->get('/floors/create', 'FloorController@create', $admin);
$router->post('/floors', 'FloorController@store', $admin);
$router->get('/floors/{id}', 'FloorController@details', $auth);
$router->get('/floors/{id}/edit', 'FloorController@edit', $admin);
$router->post('/floors/{id}', 'FloorController@update', $admin);
$router->put('/floors/{id}', 'FloorController@update', $admin);
$router->delete('/floors/{id}', 'FloorController@delete', $admin);
$router->get('/floors/{id}/rooms', 'FloorController@rooms', $auth);
$router->get('/floors/{id}/plan', 'FloorController@plan', $auth);
$router->post('/floors/{id}/plan', 'FloorController@updatePlan', $admin);
$router->put('/floors/{id}/plan', 'FloorController@updatePlan', $admin);

$router->get('/rooms', 'RoomController@index', $auth);
$router->get('/rooms/create', 'RoomController@create', array_merge($auth, $technicalStaff));
$router->post('/rooms', 'RoomController@store', array_merge($auth, $technicalStaff));
$router->get('/rooms/search', 'RoomController@searchAvailable', $auth);
$router->get('/rooms/{id}', 'RoomController@details', $auth);
$router->get('/rooms/{id}/edit', 'RoomController@edit', array_merge($auth, $technicalStaff));
$router->post('/rooms/{id}', 'RoomController@update', array_merge($auth, $technicalStaff));
$router->put('/rooms/{id}', 'RoomController@update', array_merge($auth, $technicalStaff));
$router->delete('/rooms/{id}', 'RoomController@delete', $admin);
$router->get('/rooms/{id}/availability', 'RoomController@availability', $auth);

$router->get('/schedules', 'ScheduleController@index', $auth);
$router->get('/schedules/create', 'ScheduleController@create', array_merge($auth, $scheduleManagers));
$router->post('/schedules', 'ScheduleController@store', array_merge($auth, $scheduleManagers));
$router->get('/schedules/calendar', 'ScheduleController@calendar', $auth);
$router->get('/schedules/{id}', 'ScheduleController@details', $auth);
$router->get('/schedules/{id}/edit', 'ScheduleController@edit', array_merge($auth, $scheduleManagers));
$router->post('/schedules/{id}', 'ScheduleController@update', array_merge($auth, $scheduleManagers));
$router->put('/schedules/{id}', 'ScheduleController@update', array_merge($auth, $scheduleManagers));
$router->delete('/schedules/{id}', 'ScheduleController@delete', $admin);
$router->get('/schedules/{id}/calendar', 'ScheduleController@calendar', $auth);
$router->post('/schedules/{id}/events', 'ScheduleController@attachEvent', array_merge($auth, $scheduleManagers));

$router->get('/events', 'EventController@index', array_merge($auth, $scheduleManagers));
$router->get('/events/create', 'EventController@create', array_merge($auth, $scheduleManagers));
$router->post('/events', 'EventController@store', array_merge($auth, $scheduleManagers));
$router->get('/events/{id}', 'EventController@details', array_merge($auth, $scheduleManagers));
$router->get('/events/{id}/edit', 'EventController@edit', array_merge($auth, $scheduleManagers));
$router->post('/events/{id}', 'EventController@update', array_merge($auth, $scheduleManagers));
$router->put('/events/{id}', 'EventController@update', array_merge($auth, $scheduleManagers));
$router->delete('/events/{id}', 'EventController@delete', $admin);

$router->get('/requests', 'RequestController@index', array_merge($auth, $managersOrTeachers));
$router->get('/requests/create', 'RequestController@create', array_merge($auth, $managersOrTeachers));
$router->post('/requests', 'RequestController@store', array_merge($auth, $managersOrTeachers));
$router->get('/requests/{id}', 'RequestController@details', array_merge($auth, $managersOrTeachers));
$router->get('/requests/{id}/edit', 'RequestController@edit', array_merge($auth, $managersOrTeachers));
$router->post('/requests/{id}', 'RequestController@update', array_merge($auth, $managersOrTeachers));
$router->put('/requests/{id}', 'RequestController@update', array_merge($auth, $managersOrTeachers));
$router->delete('/requests/{id}', 'RequestController@delete', array_merge($auth, $managersOrTeachers));
$router->get('/requests/{id}/review', 'RequestController@review', array_merge($auth, $scheduleManagers));
$router->post('/requests/{id}/review', 'RequestController@review', array_merge($auth, $scheduleManagers));

$router->get('/resources', 'ResourceController@index', array_merge($auth, $technicalStaff));
$router->get('/resources/create', 'ResourceController@create', array_merge($auth, $technicalStaff));
$router->post('/resources', 'ResourceController@store', array_merge($auth, $technicalStaff));
$router->get('/resources/manage', 'ResourceController@manage', array_merge($auth, $technicalStaff));
$router->post('/resources/manage', 'ResourceController@manage', array_merge($auth, $technicalStaff));
$router->get('/resources/manage/{roomId}', 'ResourceController@manage', array_merge($auth, $technicalStaff));
$router->post('/resources/manage/{roomId}', 'ResourceController@manage', array_merge($auth, $technicalStaff));
$router->get('/resources/room/{roomId}', 'ResourceController@roomResources', $auth);
$router->get('/resources/{id}', 'ResourceController@details', array_merge($auth, $technicalStaff));
$router->get('/resources/{id}/edit', 'ResourceController@edit', array_merge($auth, $technicalStaff));
$router->post('/resources/{id}', 'ResourceController@update', array_merge($auth, $technicalStaff));
$router->put('/resources/{id}', 'ResourceController@update', array_merge($auth, $technicalStaff));
$router->delete('/resources/{id}', 'ResourceController@delete', $admin);

$router->get('/routes', 'RouteController@index', $auth);
$router->get('/routes/create', 'RouteController@create', $admin);
$router->post('/routes', 'RouteController@store', $admin);
$router->get('/routes/map', 'RouteController@map', $auth);
$router->get('/routes/{id}', 'RouteController@details', $auth);
$router->get('/routes/{id}/edit', 'RouteController@edit', $admin);
$router->post('/routes/{id}', 'RouteController@update', $admin);
$router->put('/routes/{id}', 'RouteController@update', $admin);
$router->delete('/routes/{id}', 'RouteController@delete', $admin);
$router->get('/routes/{id}/map', 'RouteController@map', $auth);
$router->get('/routes/{id}/steps', 'RouteController@steps', $auth);
$router->post('/routes/{id}/steps', 'RouteController@saveSteps', $admin);

$router->get('/allocation/search', 'AllocationController@search', $auth);
$router->get('/allocation/auto-assign', 'AllocationController@autoAssign', array_merge($auth, $scheduleManagers));
$router->post('/allocation/auto-assign', 'AllocationController@autoAssign', array_merge($auth, $scheduleManagers));
$router->get('/allocation/auto-assign/{scheduleId}', 'AllocationController@autoAssign', array_merge($auth, $scheduleManagers));
$router->post('/allocation/auto-assign/{scheduleId}', 'AllocationController@autoAssign', array_merge($auth, $scheduleManagers));
$router->get('/allocation/result/{id}', 'AllocationController@result', array_merge($auth, $scheduleManagers));

$router->get('/import', 'ImportExportController@import', $admin);
$router->post('/import', 'ImportExportController@import', $admin);
$router->get('/export', 'ImportExportController@export', $admin);
$router->post('/export', 'ImportExportController@export', $admin);
$router->get('/import-export/import', 'ImportExportController@import', $admin);
$router->post('/import-export/import', 'ImportExportController@import', $admin);
$router->get('/import-export/export', 'ImportExportController@export', $admin);
$router->post('/import-export/export', 'ImportExportController@export', $admin);
$router->get('/import-export/history', 'ImportExportController@history', $admin);

$router->get('/users', 'UserController@index', $admin);
$router->get('/users/create', 'UserController@create', $admin);
$router->post('/users', 'UserController@store', $admin);
$router->get('/users/profile', 'UserController@profile', $auth);
$router->get('/users/manage', 'UserController@manage', $admin);
$router->get('/users/{id}', 'UserController@details', $admin);
$router->get('/users/{id}/edit', 'UserController@edit', $admin);
$router->post('/users/{id}', 'UserController@update', $admin);
$router->put('/users/{id}', 'UserController@update', $admin);
$router->delete('/users/{id}', 'UserController@delete', $admin);
