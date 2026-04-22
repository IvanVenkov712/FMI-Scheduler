<?php
declare(strict_types=1);

$pageTitle = 'Create Resource';
$pageSubtitle = 'Define a reusable resource such as markers, tablets, cameras, or projectors.';
$activeNav = 'resources';
$formAction = '/resources';
$submitLabel = 'Create Resource';
$fields = [
    ['name' => 'name', 'label' => 'Name'],
    ['name' => 'slug', 'label' => 'Slug'],
    ['name' => 'unit', 'label' => 'Unit'],
    ['name' => 'is_consumable', 'label' => 'Consumable Resource', 'type' => 'checkbox', 'full' => true],
    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
