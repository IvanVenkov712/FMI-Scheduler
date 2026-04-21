<?php

declare(strict_types=1);

/** @var Router $router */

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
