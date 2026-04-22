<?php
declare(strict_types=1);

$pageTitle = 'Create Building';
$pageSubtitle = 'Add a new building with orientation, address, and optional schematic notes.';
$activeNav = 'buildings';
$formAction = '/buildings';
$submitLabel = 'Create Building';
$fields = [
    ['name' => 'code', 'label' => 'Code'],
    ['name' => 'name', 'label' => 'Name'],
    ['name' => 'address', 'label' => 'Address'],
    ['name' => 'floors_count', 'label' => 'Floors Count', 'type' => 'number'],
    ['name' => 'orientation', 'label' => 'Orientation', 'type' => 'select', 'placeholder' => 'Select orientation', 'options' => [
        ['value' => 'east', 'label' => 'East'],
        ['value' => 'west', 'label' => 'West'],
        ['value' => 'north', 'label' => 'North'],
        ['value' => 'south', 'label' => 'South'],
    ]],
    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'Select status', 'options' => [
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'inactive', 'label' => 'Inactive'],
    ]],
    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
    ['name' => 'schematic_description', 'label' => 'Schematic Description', 'type' => 'textarea', 'full' => true],
    ['name' => 'vertical_view_east', 'label' => 'Vertical View East', 'type' => 'textarea', 'full' => true, 'rows' => 6],
    ['name' => 'vertical_view_west', 'label' => 'Vertical View West', 'type' => 'textarea', 'full' => true, 'rows' => 6],
    ['name' => 'vertical_view_north', 'label' => 'Vertical View North', 'type' => 'textarea', 'full' => true, 'rows' => 6],
    ['name' => 'vertical_view_south', 'label' => 'Vertical View South', 'type' => 'textarea', 'full' => true, 'rows' => 6],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
