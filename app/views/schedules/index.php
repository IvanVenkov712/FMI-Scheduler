<?php
declare(strict_types=1);

$schedules = isset($schedules) && is_array($schedules) ? $schedules : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$pageTitle = 'Schedules';
$pageSubtitle = 'Review academic schedules, attach events, and inspect generated calendars.';
$activeNav = 'schedules';
$items = $schedules;
$actions = [
    ['href' => '/schedules/create', 'label' => 'New Schedule'],
    ['href' => '/schedules/calendar', 'label' => 'Global Calendar'],
];
$filterFields = [
    ['name' => 'type', 'label' => 'Type', 'type' => 'text'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'semester', 'label' => 'Semester', 'type' => 'text'],
];
$columns = [
    ['label' => 'Name', 'render' => static fn (array $row): string => '<a href="/schedules/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape((string) ($row['name'] ?? '')) . '</a>'],
    ['label' => 'Type', 'render' => static fn (array $row): string => ui_escape((string) ($row['type'] ?? '-'))],
    ['label' => 'Academic Year', 'render' => static fn (array $row): string => ui_escape((string) ($row['academic_year'] ?? '-'))],
    ['label' => 'Semester', 'render' => static fn (array $row): string => ui_escape((string) ($row['semester'] ?? '-'))],
    ['label' => 'Starts', 'render' => static fn (array $row): string => ui_escape(ui_format_date($row['starts_on'] ?? null))],
    ['label' => 'Ends', 'render' => static fn (array $row): string => ui_escape(ui_format_date($row['ends_on'] ?? null))],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/schedules/' . ($row['id'] ?? ''), 'label' => 'Details'],
    ['href' => '/schedules/' . ($row['id'] ?? '') . '/calendar', 'label' => 'Calendar', 'class' => 'button-muted'],
    ['href' => '/schedules/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/allocation/auto-assign/' . ($row['id'] ?? ''), 'label' => 'Auto Assign', 'class' => 'button-muted'],
    ['href' => '/schedules/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this schedule?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
