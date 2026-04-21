<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);
$baseUrl = getenv('APP_URL') ?: 'http://localhost/WebProject/public';

return [
    'name' => getenv('APP_NAME') ?: 'Schedule Management System',
    'description' => 'System for managing schedules, buildings, floors, rooms, routes, resources and movement constraints.',
    'environment' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim($baseUrl, '/'),
    'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Sofia',
    'locale' => getenv('APP_LOCALE') ?: 'bg',
    'encoding' => 'UTF-8',

    'paths' => [
        'root' => $rootPath,
        'app' => $rootPath . DIRECTORY_SEPARATOR . 'app',
        'config' => $rootPath . DIRECTORY_SEPARATOR . 'config',
        'database' => $rootPath . DIRECTORY_SEPARATOR . 'database',
        'public' => $rootPath . DIRECTORY_SEPARATOR . 'public',
        'storage' => $rootPath . DIRECTORY_SEPARATOR . 'storage',
        'views' => $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views',
        'uploads' => $rootPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
        'imports' => $rootPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'imports',
        'plans' => $rootPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'plans',
        'exports' => $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'exports',
        'logs' => $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
        'cache' => $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache',
        'temp' => $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp',
    ],

    'session' => [
        'name' => getenv('SESSION_NAME') ?: 'schedule_management_session',
        'lifetime_minutes' => (int) (getenv('SESSION_LIFETIME') ?: 120),
    ],

    'logging' => [
        'channel' => getenv('LOG_CHANNEL') ?: 'file',
        'level' => getenv('LOG_LEVEL') ?: 'error',
        'error_file' => $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'error.log',
    ],

    'features' => [
        'building_editor' => true,
        'drag_and_drop_plans' => true,
        'vertical_building_views' => true,
        'room_availability_search' => true,
        'automatic_allocation' => true,
        'covid_movement_constraints' => true,
        'import_export' => true,
        'audit_logs' => true,
    ],

    'uploads' => [
        'max_file_size' => 5 * 1024 * 1024,
        'allowed_import_extensions' => ['csv', 'json', 'xml', 'txt', 'ascii'],
        'allowed_plan_extensions' => ['txt', 'json', 'xml'],
    ],

    'pagination' => [
        'per_page' => 20,
    ],
];
