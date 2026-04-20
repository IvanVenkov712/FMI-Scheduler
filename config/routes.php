<?php

declare(strict_types=1);

/** @var Router $router */

$router->get('/', 'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@authenticate');
$router->get('/register', 'AuthController@register');
$router->post('/register', 'AuthController@storeRegistration');
$router->post('/logout', 'AuthController@logout');
$router->get('/logout', 'AuthController@logout');

$router->get('/buildings', 'BuildingController@index');
$router->get('/buildings/create', 'BuildingController@create');
$router->post('/buildings', 'BuildingController@store');
$router->get('/buildings/{id}', 'BuildingController@details');
$router->get('/buildings/{id}/edit', 'BuildingController@edit');
$router->post('/buildings/{id}', 'BuildingController@update');
$router->put('/buildings/{id}', 'BuildingController@update');
$router->delete('/buildings/{id}', 'BuildingController@delete');
$router->get('/buildings/{id}/floors', 'BuildingController@floors');
$router->get('/buildings/{id}/vertical-view/{orientation}', 'BuildingController@verticalView');

$router->get('/floors', 'FloorController@index');
$router->get('/floors/create', 'FloorController@create');
$router->post('/floors', 'FloorController@store');
$router->get('/floors/{id}', 'FloorController@details');
$router->get('/floors/{id}/edit', 'FloorController@edit');
$router->post('/floors/{id}', 'FloorController@update');
$router->put('/floors/{id}', 'FloorController@update');
$router->delete('/floors/{id}', 'FloorController@delete');
$router->get('/floors/{id}/rooms', 'FloorController@rooms');
$router->get('/floors/{id}/plan', 'FloorController@plan');
$router->post('/floors/{id}/plan', 'FloorController@updatePlan');
$router->put('/floors/{id}/plan', 'FloorController@updatePlan');

$router->get('/rooms', 'RoomController@index');
$router->get('/rooms/create', 'RoomController@create');
$router->post('/rooms', 'RoomController@store');
$router->get('/rooms/search', 'RoomController@searchAvailable');
$router->get('/rooms/{id}', 'RoomController@details');
$router->get('/rooms/{id}/edit', 'RoomController@edit');
$router->post('/rooms/{id}', 'RoomController@update');
$router->put('/rooms/{id}', 'RoomController@update');
$router->delete('/rooms/{id}', 'RoomController@delete');
$router->get('/rooms/{id}/availability', 'RoomController@availability');

$router->get('/schedules', 'ScheduleController@index');
$router->get('/schedules/create', 'ScheduleController@create');
$router->post('/schedules', 'ScheduleController@store');
$router->get('/schedules/calendar', 'ScheduleController@calendar');
$router->get('/schedules/{id}', 'ScheduleController@details');
$router->get('/schedules/{id}/edit', 'ScheduleController@edit');
$router->post('/schedules/{id}', 'ScheduleController@update');
$router->put('/schedules/{id}', 'ScheduleController@update');
$router->delete('/schedules/{id}', 'ScheduleController@delete');
$router->get('/schedules/{id}/calendar', 'ScheduleController@calendar');
$router->post('/schedules/{id}/events', 'ScheduleController@attachEvent');

$router->get('/events', 'EventController@index');
$router->get('/events/create', 'EventController@create');
$router->post('/events', 'EventController@store');
$router->get('/events/{id}', 'EventController@details');
$router->get('/events/{id}/edit', 'EventController@edit');
$router->post('/events/{id}', 'EventController@update');
$router->put('/events/{id}', 'EventController@update');
$router->delete('/events/{id}', 'EventController@delete');

$router->get('/requests', 'RequestController@index');
$router->get('/requests/create', 'RequestController@create');
$router->post('/requests', 'RequestController@store');
$router->get('/requests/{id}', 'RequestController@details');
$router->get('/requests/{id}/edit', 'RequestController@edit');
$router->post('/requests/{id}', 'RequestController@update');
$router->put('/requests/{id}', 'RequestController@update');
$router->delete('/requests/{id}', 'RequestController@delete');
$router->get('/requests/{id}/review', 'RequestController@review');
$router->post('/requests/{id}/review', 'RequestController@review');

$router->get('/resources', 'ResourceController@index');
$router->get('/resources/create', 'ResourceController@create');
$router->post('/resources', 'ResourceController@store');
$router->get('/resources/manage', 'ResourceController@manage');
$router->post('/resources/manage', 'ResourceController@manage');
$router->get('/resources/manage/{roomId}', 'ResourceController@manage');
$router->post('/resources/manage/{roomId}', 'ResourceController@manage');
$router->get('/resources/room/{roomId}', 'ResourceController@roomResources');
$router->get('/resources/{id}', 'ResourceController@details');
$router->get('/resources/{id}/edit', 'ResourceController@edit');
$router->post('/resources/{id}', 'ResourceController@update');
$router->put('/resources/{id}', 'ResourceController@update');
$router->delete('/resources/{id}', 'ResourceController@delete');

$router->get('/routes', 'RouteController@index');
$router->get('/routes/create', 'RouteController@create');
$router->post('/routes', 'RouteController@store');
$router->get('/routes/map', 'RouteController@map');
$router->get('/routes/{id}', 'RouteController@details');
$router->get('/routes/{id}/edit', 'RouteController@edit');
$router->post('/routes/{id}', 'RouteController@update');
$router->put('/routes/{id}', 'RouteController@update');
$router->delete('/routes/{id}', 'RouteController@delete');
$router->get('/routes/{id}/map', 'RouteController@map');
$router->get('/routes/{id}/steps', 'RouteController@steps');
$router->post('/routes/{id}/steps', 'RouteController@saveSteps');

$router->get('/allocation/search', 'AllocationController@search');
$router->get('/allocation/auto-assign', 'AllocationController@autoAssign');
$router->post('/allocation/auto-assign', 'AllocationController@autoAssign');
$router->get('/allocation/auto-assign/{scheduleId}', 'AllocationController@autoAssign');
$router->post('/allocation/auto-assign/{scheduleId}', 'AllocationController@autoAssign');
$router->get('/allocation/result/{id}', 'AllocationController@result');

$router->get('/import', 'ImportExportController@import');
$router->post('/import', 'ImportExportController@import');
$router->get('/export', 'ImportExportController@export');
$router->post('/export', 'ImportExportController@export');
$router->get('/import-export/import', 'ImportExportController@import');
$router->post('/import-export/import', 'ImportExportController@import');
$router->get('/import-export/export', 'ImportExportController@export');
$router->post('/import-export/export', 'ImportExportController@export');
$router->get('/import-export/history', 'ImportExportController@history');

$router->get('/users', 'UserController@index');
$router->get('/users/create', 'UserController@create');
$router->post('/users', 'UserController@store');
$router->get('/users/profile', 'UserController@profile');
$router->get('/users/manage', 'UserController@manage');
$router->get('/users/{id}', 'UserController@details');
$router->get('/users/{id}/edit', 'UserController@edit');
$router->post('/users/{id}', 'UserController@update');
$router->put('/users/{id}', 'UserController@update');
$router->delete('/users/{id}', 'UserController@delete');

$router->get('/api/dashboard', 'DashboardController@index');
$router->get('/api/buildings', 'BuildingController@index');
$router->post('/api/buildings', 'BuildingController@store');
$router->get('/api/buildings/{id}', 'BuildingController@details');
$router->put('/api/buildings/{id}', 'BuildingController@update');
$router->delete('/api/buildings/{id}', 'BuildingController@delete');
$router->get('/api/floors', 'FloorController@index');
$router->post('/api/floors', 'FloorController@store');
$router->get('/api/floors/{id}/plan', 'FloorController@plan');
$router->put('/api/floors/{id}/plan', 'FloorController@updatePlan');
$router->get('/api/rooms', 'RoomController@index');
$router->post('/api/rooms', 'RoomController@store');
$router->get('/api/rooms/search', 'RoomController@searchAvailable');
$router->get('/api/rooms/{id}/availability', 'RoomController@availability');
$router->get('/api/schedules', 'ScheduleController@index');
$router->post('/api/schedules', 'ScheduleController@store');
$router->get('/api/schedules/{id}/calendar', 'ScheduleController@calendar');
$router->post('/api/schedules/{id}/events', 'ScheduleController@attachEvent');
$router->get('/api/events', 'EventController@index');
$router->post('/api/events', 'EventController@store');
$router->put('/api/events/{id}', 'EventController@update');
$router->get('/api/requests', 'RequestController@index');
$router->post('/api/requests', 'RequestController@store');
$router->post('/api/requests/{id}/review', 'RequestController@review');
$router->get('/api/resources', 'ResourceController@index');
$router->post('/api/resources', 'ResourceController@store');
$router->get('/api/resources/room/{roomId}', 'ResourceController@roomResources');
$router->get('/api/routes', 'RouteController@index');
$router->post('/api/routes', 'RouteController@store');
$router->get('/api/routes/{id}/steps', 'RouteController@steps');
$router->post('/api/routes/{id}/steps', 'RouteController@saveSteps');
$router->get('/api/allocation/search', 'AllocationController@search');
$router->post('/api/allocation/auto-assign', 'AllocationController@autoAssign');
$router->post('/api/allocation/auto-assign/{scheduleId}', 'AllocationController@autoAssign');
$router->get('/api/allocation/result/{id}', 'AllocationController@result');
$router->post('/api/import', 'ImportExportController@import');
$router->get('/api/export', 'ImportExportController@export');
