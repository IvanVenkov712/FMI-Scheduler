<?php
declare(strict_types=1);

$resource = isset($resource) && is_array($resource) ? $resource : [];
$pageTitle = 'Edit Resource';
$pageSubtitle = 'Update naming, units, and whether the resource is consumable.';
$activeNav = 'resources';
$formAction = '/resources/' . (string) ($resource['id'] ?? '');
$formMethod = 'PUT';
$submitLabel = 'Save Resource';
$entity = $resource;
$fields = [
    ['name' => 'name', 'label' => 'Name'],
    ['name' => 'slug', 'label' => 'Slug'],
    ['name' => 'unit', 'label' => 'Unit'],
    ['name' => 'is_consumable', 'label' => 'Consumable Resource', 'type' => 'checkbox', 'full' => true],
    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
