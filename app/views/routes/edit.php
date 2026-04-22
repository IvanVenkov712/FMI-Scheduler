<?php
declare(strict_types=1);

$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
$buildings = isset($buildings) && is_array($buildings) ? $buildings : [];
$route = isset($route) && is_array($route) ? $route : [];
$pageTitle = 'Edit Route';
$pageSubtitle = 'Adjust durations, connectivity, and accessibility properties.';
$activeNav = 'routes';
$formAction = '/routes/' . (string) ($route['id'] ?? '');
$formMethod = 'PUT';
$submitLabel = 'Save Route';
$entity = $route;
$fields = [
    ['name' => 'name', 'label' => 'Name'],
    ['name' => 'from_room_id', 'label' => 'From Room', 'type' => 'select', 'placeholder' => 'Optional room', 'options' => array_map(static fn (array $row): array => ['value' => (string) ($row['id'] ?? ''), 'label' => (string) (($row['code'] ?? '') . ' - ' . ($row['name'] ?? ''))], $rooms)],
    ['name' => 'to_room_id', 'label' => 'To Room', 'type' => 'select', 'placeholder' => 'Optional room', 'options' => array_map(static fn (array $row): array => ['value' => (string) ($row['id'] ?? ''), 'label' => (string) (($row['code'] ?? '') . ' - ' . ($row['name'] ?? ''))], $rooms)],
    ['name' => 'from_building_id', 'label' => 'From Building', 'type' => 'select', 'placeholder' => 'Optional building', 'options' => array_map(static fn (array $row): array => ['value' => (string) ($row['id'] ?? ''), 'label' => (string) (($row['code'] ?? '') . ' - ' . ($row['name'] ?? ''))], $buildings)],
    ['name' => 'to_building_id', 'label' => 'To Building', 'type' => 'select', 'placeholder' => 'Optional building', 'options' => array_map(static fn (array $row): array => ['value' => (string) ($row['id'] ?? ''), 'label' => (string) (($row['code'] ?? '') . ' - ' . ($row['name'] ?? ''))], $buildings)],
    ['name' => 'distance_meters', 'label' => 'Distance (m)', 'type' => 'number'],
    ['name' => 'walking_duration_minutes', 'label' => 'Walking Duration', 'type' => 'number'],
    ['name' => 'car_duration_minutes', 'label' => 'Car Duration', 'type' => 'number'],
    ['name' => 'public_transport_duration_minutes', 'label' => 'Public Transport Duration', 'type' => 'number'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'is_accessible', 'label' => 'Accessible Route', 'type' => 'checkbox', 'full' => true],
    ['name' => 'is_bidirectional', 'label' => 'Bidirectional', 'type' => 'checkbox', 'full' => true],
    ['name' => 'path_data', 'label' => 'Path Data JSON', 'type' => 'textarea', 'full' => true],
    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
