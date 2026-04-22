<?php
declare(strict_types=1);

$floors = isset($floors) && is_array($floors) ? $floors : [];
$roomTypes = isset($room_types) && is_array($room_types) ? $room_types : [];
$room = isset($room) && is_array($room) ? $room : [];
$pageTitle = 'Edit Room';
$pageSubtitle = 'Update location, capacity, and online delivery settings for this room.';
$activeNav = 'rooms';
$formAction = '/rooms/' . (string) ($room['id'] ?? '');
$formMethod = 'PUT';
$submitLabel = 'Save Room';
$entity = $room;
$fields = [
    ['name' => 'floor_id', 'label' => 'Floor', 'type' => 'select', 'placeholder' => 'Select floor', 'options' => array_map(static fn (array $row): array => [
        'value' => (string) ($row['id'] ?? ''),
        'label' => 'Building ' . ($row['building_id'] ?? '?') . ' / Floor ' . ($row['number'] ?? '?') . ' - ' . ($row['name'] ?? ''),
    ], $floors)],
    ['name' => 'room_type_id', 'label' => 'Room Type', 'type' => 'select', 'placeholder' => 'Select type', 'options' => array_map(static fn (array $row): array => [
        'value' => (string) ($row['id'] ?? ''),
        'label' => (string) ($row['name'] ?? ''),
    ], $roomTypes)],
    ['name' => 'code', 'label' => 'Code'],
    ['name' => 'name', 'label' => 'Name'],
    ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number'],
    ['name' => 'covid_capacity', 'label' => 'Covid Capacity', 'type' => 'number'],
    ['name' => 'x', 'label' => 'X', 'type' => 'number'],
    ['name' => 'y', 'label' => 'Y', 'type' => 'number'],
    ['name' => 'width', 'label' => 'Width', 'type' => 'number'],
    ['name' => 'height', 'label' => 'Height', 'type' => 'number'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'online_url', 'label' => 'Online URL', 'full' => true],
    ['name' => 'access_key', 'label' => 'Access Key', 'full' => true],
    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
