<?php
declare(strict_types=1);

$schedule = isset($schedule) && is_array($schedule) ? $schedule : [];
$pageTitle = 'Edit Schedule';
$pageSubtitle = 'Update period, type, and status for this schedule.';
$activeNav = 'schedules';
$formAction = '/schedules/' . (string) ($schedule['id'] ?? '');
$formMethod = 'PUT';
$submitLabel = 'Save Schedule';
$entity = $schedule;
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
