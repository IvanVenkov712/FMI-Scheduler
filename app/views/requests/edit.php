<?php
declare(strict_types=1);

$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
$request = isset($request) && is_array($request) ? $request : [];
$pageTitle = 'Edit Request';
$pageSubtitle = 'Update request timing, preferences, and required resource payload.';
$activeNav = 'requests';
$formAction = '/requests/' . (string) ($request['id'] ?? '');
$formMethod = 'PUT';
$submitLabel = 'Save Request';
$entity = $request;
$fields = [
    ['name' => 'course_name', 'label' => 'Course Name'],
    ['name' => 'students_count', 'label' => 'Students Count', 'type' => 'number'],
    ['name' => 'preferred_room_id', 'label' => 'Preferred Room', 'type' => 'select', 'placeholder' => 'Select room', 'options' => array_map(static fn (array $row): array => [
        'value' => (string) ($row['id'] ?? ''),
        'label' => (string) (($row['code'] ?? '') . ' - ' . ($row['name'] ?? '')),
    ], $rooms)],
    ['name' => 'preferred_delivery_mode', 'label' => 'Delivery Mode', 'type' => 'select', 'placeholder' => 'Select mode', 'options' => [
        ['value' => 'physical', 'label' => 'Physical'],
        ['value' => 'online', 'label' => 'Online'],
        ['value' => 'hybrid', 'label' => 'Hybrid'],
    ]],
    ['name' => 'preferred_starts_at', 'label' => 'Preferred Starts At', 'type' => 'datetime-local'],
    ['name' => 'preferred_ends_at', 'label' => 'Preferred Ends At', 'type' => 'datetime-local'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'online_platform', 'label' => 'Online Platform'],
    ['name' => 'online_url', 'label' => 'Online URL'],
    ['name' => 'online_access_key', 'label' => 'Online Access Key'],
    ['name' => 'online_access_password', 'label' => 'Online Access Password'],
    ['name' => 'online_moderator_name', 'label' => 'Moderator Name'],
    ['name' => 'required_resources', 'label' => 'Required Resources JSON', 'type' => 'textarea', 'full' => true, 'rows' => 6],
    ['name' => 'online_access_instructions', 'label' => 'Access Instructions', 'type' => 'textarea', 'full' => true],
    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'full' => true],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
