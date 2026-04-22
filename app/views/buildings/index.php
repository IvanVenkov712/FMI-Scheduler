<?php
declare(strict_types=1);

$buildings = isset($buildings) && is_array($buildings) ? $buildings : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$pageTitle = 'Buildings';
$pageSubtitle = 'Manage campus buildings and jump into floors or vertical building views.';
$activeNav = 'buildings';
$items = $buildings;
$actions = [['href' => '/buildings/create', 'label' => 'New Building']];
$filterFields = [
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
    ['name' => 'orientation', 'label' => 'Orientation', 'type' => 'select', 'options' => ['east' => 'East', 'west' => 'West', 'north' => 'North', 'south' => 'South']],
];
$columns = [
    ['label' => 'Code', 'render' => static fn (array $row): string => ui_escape((string) ($row['code'] ?? ''))],
    ['label' => 'Name', 'render' => static fn (array $row): string => '<a href="/buildings/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape((string) ($row['name'] ?? '')) . '</a>'],
    ['label' => 'Address', 'render' => static fn (array $row): string => ui_escape((string) ($row['address'] ?? '-'))],
    ['label' => 'Floors', 'render' => static fn (array $row): string => ui_escape((string) ($row['floors_count'] ?? '-'))],
    ['label' => 'Status', 'render' => static fn (array $row): string => '<span class="' . ui_escape(ui_status_class($row['status'] ?? '')) . '">' . ui_escape((string) ($row['status'] ?? '')) . '</span>'],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/buildings/' . ($row['id'] ?? ''), 'label' => 'Details'],
    ['href' => '/buildings/' . ($row['id'] ?? '') . '/floors', 'label' => 'Floors', 'class' => 'button-muted'],
    ['href' => '/buildings/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/buildings/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this building?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
