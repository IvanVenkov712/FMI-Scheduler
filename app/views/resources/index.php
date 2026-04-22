<?php
declare(strict_types=1);

$resources = isset($resources) && is_array($resources) ? $resources : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$pageTitle = 'Resources';
$pageSubtitle = 'Manage reusable room resources and assign them to physical rooms.';
$activeNav = 'resources';
$items = $resources;
$actions = [
    ['href' => '/resources/create', 'label' => 'New Resource'],
    ['href' => '/resources/manage', 'label' => 'Assign to Room'],
];
$columns = [
    ['label' => 'Name', 'render' => static fn (array $row): string => '<a href="/resources/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape((string) ($row['name'] ?? '')) . '</a>'],
    ['label' => 'Slug', 'render' => static fn (array $row): string => ui_escape((string) ($row['slug'] ?? '-'))],
    ['label' => 'Unit', 'render' => static fn (array $row): string => ui_escape((string) ($row['unit'] ?? '-'))],
    ['label' => 'Consumable', 'render' => static fn (array $row): string => ui_escape(ui_bool_label($row['is_consumable'] ?? 0))],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/resources/' . ($row['id'] ?? ''), 'label' => 'Details'],
    ['href' => '/resources/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/resources/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this resource?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
