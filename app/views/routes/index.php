<?php
declare(strict_types=1);

$routes = isset($routes) && is_array($routes) ? $routes : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$pageTitle = 'Routes';
$pageSubtitle = 'Model walking, car, and public transport movement between rooms and buildings.';
$activeNav = 'routes';
$items = $routes;
$actions = [
    ['href' => '/routes/create', 'label' => 'New Route'],
    ['href' => '/routes/map', 'label' => 'Route Map'],
];
$filterFields = [
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'is_accessible', 'label' => 'Accessible', 'type' => 'text'],
    ['name' => 'from_building_id', 'label' => 'From Building', 'type' => 'number'],
    ['name' => 'to_building_id', 'label' => 'To Building', 'type' => 'number'],
];
$columns = [
    ['label' => 'Name', 'render' => static fn (array $row): string => '<a href="/routes/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape((string) ($row['name'] ?? '')) . '</a>'],
    ['label' => 'From', 'render' => static fn (array $row): string => ui_escape((string) ($row['from_room_id'] ?? $row['from_building_id'] ?? '-'))],
    ['label' => 'To', 'render' => static fn (array $row): string => ui_escape((string) ($row['to_room_id'] ?? $row['to_building_id'] ?? '-'))],
    ['label' => 'Walking', 'render' => static fn (array $row): string => ui_escape((string) ($row['walking_duration_minutes'] ?? '-')) . ' min'],
    ['label' => 'Accessible', 'render' => static fn (array $row): string => ui_escape(ui_bool_label($row['is_accessible'] ?? 0))],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/routes/' . ($row['id'] ?? ''), 'label' => 'Details'],
    ['href' => '/routes/' . ($row['id'] ?? '') . '/map', 'label' => 'Map', 'class' => 'button-muted'],
    ['href' => '/routes/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/routes/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this route?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
