<?php
declare(strict_types=1);

$resource = isset($resource) && is_array($resource) ? $resource : [];
$pageTitle = (string) ($resource['name'] ?? 'Resource Details');
$pageSubtitle = 'Inspect resource metadata and jump to room assignment management.';
$activeNav = 'resources';
$summary = [
    'Name' => ui_escape((string) ($resource['name'] ?? '-')),
    'Slug' => ui_escape((string) ($resource['slug'] ?? '-')),
    'Unit' => ui_escape((string) ($resource['unit'] ?? '-')),
    'Consumable' => ui_escape(ui_bool_label($resource['is_consumable'] ?? 0)),
    'Description' => ui_escape((string) ($resource['description'] ?? '-')),
];
$actions = [
    ['href' => '/resources/' . ($resource['id'] ?? '') . '/edit', 'label' => 'Edit Resource'],
    ['href' => '/resources/manage', 'label' => 'Assign to Room', 'class' => 'button-muted'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-details.php';
