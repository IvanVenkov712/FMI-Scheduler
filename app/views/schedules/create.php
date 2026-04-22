<?php
declare(strict_types=1);

$pageTitle = 'Create Schedule';
$pageSubtitle = 'Create a new schedule shell before attaching events or running allocation.';
$activeNav = 'schedules';
$formAction = '/schedules';
$submitLabel = 'Create Schedule';
$fields = [
    ['name' => 'name', 'label' => 'Name'],
    ['name' => 'type', 'label' => 'Type'],
    ['name' => 'academic_year', 'label' => 'Academic Year'],
    ['name' => 'semester', 'label' => 'Semester'],
    ['name' => 'starts_on', 'label' => 'Starts On', 'type' => 'date'],
    ['name' => 'ends_on', 'label' => 'Ends On', 'type' => 'date'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
