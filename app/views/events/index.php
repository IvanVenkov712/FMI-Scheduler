<?php
declare(strict_types=1);

$events = isset($events) && is_array($events) ? $events : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$pageTitle = 'Events';
$pageSubtitle = 'Create and review lectures, exams, consultations, and other scheduled activities.';
$activeNav = 'events';
$items = $events;
$actions = [['href' => '/events/create', 'label' => 'New Event']];
$filterFields = [
    ['name' => 'type', 'label' => 'Type', 'type' => 'text'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'room_id', 'label' => 'Room ID', 'type' => 'number'],
    ['name' => 'delivery_mode', 'label' => 'Delivery Mode', 'type' => 'text'],
];
$columns = [
    ['label' => 'Title', 'render' => static fn (array $row): string => '<a href="/events/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape((string) ($row['title'] ?? '')) . '</a>'],
    ['label' => 'Type', 'render' => static fn (array $row): string => ui_escape((string) ($row['type'] ?? '-'))],
    ['label' => 'Starts', 'render' => static fn (array $row): string => ui_escape(ui_format_datetime($row['starts_at'] ?? null))],
    ['label' => 'Ends', 'render' => static fn (array $row): string => ui_escape(ui_format_datetime($row['ends_at'] ?? null))],
    ['label' => 'Room', 'render' => static fn (array $row): string => ui_escape((string) ($row['room_id'] ?? '-'))],
    ['label' => 'Status', 'render' => static fn (array $row): string => '<span class="' . ui_escape(ui_status_class($row['status'] ?? '')) . '">' . ui_escape((string) ($row['status'] ?? '')) . '</span>'],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/events/' . ($row['id'] ?? ''), 'label' => 'Details'],
    ['href' => '/events/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/events/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this event?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
