<?php
declare(strict_types=1);

$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$floor = isset($floor) && is_array($floor) ? $floor : null;
$pageTitle = $floor !== null ? 'Rooms on Floor ' . (string) ($floor['name'] ?? $floor['number'] ?? '') : 'Rooms';
$pageSubtitle = 'Browse rooms, inspect capacity and delivery mode support, or run availability checks.';
$activeNav = 'rooms';
$items = $rooms;
$actions = [
    ['href' => '/rooms/create', 'label' => 'New Room'],
    ['href' => '/rooms/search', 'label' => 'Search Available Rooms'],
];
$filterFields = [
    ['name' => 'floor_id', 'label' => 'Floor ID', 'type' => 'number'],
    ['name' => 'room_type_id', 'label' => 'Room Type ID', 'type' => 'number'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
];
$columns = [
    ['label' => 'Code', 'render' => static fn (array $row): string => '<a href="/rooms/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape((string) ($row['code'] ?? '')) . '</a>'],
    ['label' => 'Name', 'render' => static fn (array $row): string => ui_escape((string) ($row['name'] ?? '-'))],
    ['label' => 'Floor', 'render' => static fn (array $row): string => ui_escape((string) ($row['floor_id'] ?? '-'))],
    ['label' => 'Capacity', 'render' => static fn (array $row): string => ui_escape((string) ($row['capacity'] ?? '-'))],
    ['label' => 'Mode', 'render' => static fn (array $row): string => ($row['online_url'] ?? null) ? 'Hybrid / Online enabled' : 'Physical'],
    ['label' => 'Status', 'render' => static fn (array $row): string => '<span class="' . ui_escape(ui_status_class($row['status'] ?? '')) . '">' . ui_escape((string) ($row['status'] ?? '')) . '</span>'],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/rooms/' . ($row['id'] ?? ''), 'label' => 'Details'],
    ['href' => '/rooms/' . ($row['id'] ?? '') . '/availability', 'label' => 'Availability', 'class' => 'button-muted'],
    ['href' => '/rooms/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/rooms/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this room?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
