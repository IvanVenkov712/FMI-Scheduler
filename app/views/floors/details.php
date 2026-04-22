<?php
declare(strict_types=1);

$floor = isset($floor) && is_array($floor) ? $floor : [];
$pageTitle = (string) ($floor['name'] ?? 'Floor Details');
$pageSubtitle = 'Inspect floor metadata and jump to its rooms or plan editor.';
$activeNav = 'floors';
$summary = [
    'ID' => ui_escape((string) ($floor['id'] ?? '-')),
    'Building ID' => ui_escape((string) ($floor['building_id'] ?? '-')),
    'Number' => ui_escape((string) ($floor['number'] ?? '-')),
    'Capacity' => ui_escape((string) ($floor['capacity'] ?? '-')),
    'Width Units' => ui_escape((string) ($floor['width_units'] ?? '-')),
    'Height Units' => ui_escape((string) ($floor['height_units'] ?? '-')),
    'Status' => '<span class="' . ui_escape(ui_status_class($floor['status'] ?? '')) . '">' . ui_escape((string) ($floor['status'] ?? '-')) . '</span>',
    'Description' => ui_escape((string) ($floor['description'] ?? '-')),
];
$actions = [
    ['href' => '/floors/' . ($floor['id'] ?? '') . '/edit', 'label' => 'Edit Floor'],
    ['href' => '/floors/' . ($floor['id'] ?? '') . '/plan', 'label' => 'Edit Plan', 'class' => 'button-muted'],
    ['href' => '/floors/' . ($floor['id'] ?? '') . '/rooms', 'label' => 'Open Rooms', 'class' => 'button-muted'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-details.php';
