<?php
declare(strict_types=1);

$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
$event = isset($event) && is_array($event) ? $event : [];
$onlineSession = isset($online_session) && is_array($online_session) ? $online_session : [];
$event += [
    'online_platform' => $onlineSession['platform'] ?? null,
    'online_url' => $onlineSession['url'] ?? null,
    'online_access_key' => $onlineSession['access_key'] ?? null,
    'online_access_password' => $onlineSession['access_password'] ?? null,
    'online_moderator_name' => $onlineSession['moderator_name'] ?? null,
    'online_access_instructions' => $onlineSession['access_instructions'] ?? null,
];
$pageTitle = 'Edit Event';
$pageSubtitle = 'Update timing, assigned room, and online session settings.';
$activeNav = 'events';
$formAction = '/events/' . (string) ($event['id'] ?? '');
$formMethod = 'PUT';
$submitLabel = 'Save Event';
$entity = $event;
$fields = [
    ['name' => 'title', 'label' => 'Title'],
    ['name' => 'type', 'label' => 'Type'],
    ['name' => 'course_name', 'label' => 'Course Name'],
    ['name' => 'group_name', 'label' => 'Group Name'],
    ['name' => 'students_count', 'label' => 'Students Count', 'type' => 'number'],
    ['name' => 'delivery_mode', 'label' => 'Delivery Mode', 'type' => 'select', 'placeholder' => 'Select mode', 'options' => [
        ['value' => 'physical', 'label' => 'Physical'],
        ['value' => 'online', 'label' => 'Online'],
        ['value' => 'hybrid', 'label' => 'Hybrid'],
    ]],
    ['name' => 'room_id', 'label' => 'Room', 'type' => 'select', 'placeholder' => 'Select room', 'options' => array_map(static fn (array $row): array => [
        'value' => (string) ($row['id'] ?? ''),
        'label' => (string) (($row['code'] ?? '') . ' - ' . ($row['name'] ?? '')),
    ], $rooms)],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'starts_at', 'label' => 'Starts At', 'type' => 'datetime-local'],
    ['name' => 'ends_at', 'label' => 'Ends At', 'type' => 'datetime-local'],
    ['name' => 'online_platform', 'label' => 'Online Platform'],
    ['name' => 'online_url', 'label' => 'Online URL'],
    ['name' => 'online_access_key', 'label' => 'Access Key'],
    ['name' => 'online_access_password', 'label' => 'Access Password'],
    ['name' => 'online_moderator_name', 'label' => 'Moderator Name'],
    ['name' => 'online_access_instructions', 'label' => 'Access Instructions', 'type' => 'textarea', 'full' => true],
    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
