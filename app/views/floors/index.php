<?php
declare(strict_types=1);

$floors = isset($floors) && is_array($floors) ? $floors : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$building = isset($building) && is_array($building) ? $building : null;
$pageTitle = $building !== null ? 'Floors in ' . (string) ($building['name'] ?? 'Building') : 'Floors';
$pageSubtitle = 'Review floor metadata, plans, and linked rooms.';
$activeNav = 'floors';
$items = $floors;
$actions = [['href' => '/floors/create', 'label' => 'New Floor']];
$filterFields = [
    ['name' => 'building_id', 'label' => 'Building ID', 'type' => 'number'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
];
$columns = [
    ['label' => 'ID', 'render' => static fn (array $row): string => ui_escape((string) ($row['id'] ?? ''))],
    ['label' => 'Name', 'render' => static fn (array $row): string => '<a href="/floors/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape((string) ($row['name'] ?? '')) . '</a>'],
    ['label' => 'Building', 'render' => static fn (array $row): string => ui_escape((string) ($row['building_id'] ?? '-'))],
    ['label' => 'Number', 'render' => static fn (array $row): string => ui_escape((string) ($row['number'] ?? '-'))],
    ['label' => 'Capacity', 'render' => static fn (array $row): string => ui_escape((string) ($row['capacity'] ?? '-'))],
    ['label' => 'Status', 'render' => static fn (array $row): string => '<span class="' . ui_escape(ui_status_class($row['status'] ?? '')) . '">' . ui_escape((string) ($row['status'] ?? '')) . '</span>'],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/floors/' . ($row['id'] ?? ''), 'label' => 'Details'],
    ['href' => '/floors/' . ($row['id'] ?? '') . '/plan', 'label' => 'Plan', 'class' => 'button-muted'],
    ['href' => '/floors/' . ($row['id'] ?? '') . '/rooms', 'label' => 'Rooms', 'class' => 'button-muted'],
    ['href' => '/floors/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/floors/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this floor?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
