<?php
declare(strict_types=1);

$buildings = isset($buildings) && is_array($buildings) ? $buildings : [];
$pageTitle = 'Create Floor';
$pageSubtitle = 'Add a new floor and optionally seed its ASCII plan.';
$activeNav = 'floors';
$formAction = '/floors';
$submitLabel = 'Create Floor';
$fields = [
    ['name' => 'building_id', 'label' => 'Building', 'type' => 'select', 'placeholder' => 'Select building', 'options' => array_map(static fn (array $row): array => [
        'value' => (string) ($row['id'] ?? ''),
        'label' => (string) (($row['code'] ?? '') . ' - ' . ($row['name'] ?? '')),
    ], $buildings)],
    ['name' => 'number', 'label' => 'Floor Number', 'type' => 'number'],
    ['name' => 'name', 'label' => 'Floor Name'],
    ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number'],
    ['name' => 'width_units', 'label' => 'Width Units', 'type' => 'number'],
    ['name' => 'height_units', 'label' => 'Height Units', 'type' => 'number'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'Select status', 'options' => [
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'inactive', 'label' => 'Inactive'],
    ]],
    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
    ['name' => 'ascii_plan', 'label' => 'ASCII Plan', 'type' => 'textarea', 'full' => true, 'rows' => 10],
    ['name' => 'plan_data', 'label' => 'Plan Data JSON', 'type' => 'textarea', 'full' => true, 'rows' => 8],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
