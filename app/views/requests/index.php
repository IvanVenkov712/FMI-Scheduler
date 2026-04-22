<?php
declare(strict_types=1);

$requests = isset($requests) && is_array($requests) ? $requests : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$pageTitle = 'Exam Requests';
$pageSubtitle = 'Create, review, and schedule exam or event requests.';
$activeNav = 'requests';
$items = $requests;
$actions = [['href' => '/requests/create', 'label' => 'New Request']];
$filterFields = [
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'requested_by', 'label' => 'Requested By', 'type' => 'number'],
    ['name' => 'preferred_delivery_mode', 'label' => 'Mode', 'type' => 'text'],
];
$columns = [
    ['label' => 'Course', 'render' => static fn (array $row): string => '<a href="/requests/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape((string) ($row['course_name'] ?? '')) . '</a>'],
    ['label' => 'Students', 'render' => static fn (array $row): string => ui_escape((string) ($row['students_count'] ?? '-'))],
    ['label' => 'Mode', 'render' => static fn (array $row): string => ui_escape((string) ($row['preferred_delivery_mode'] ?? '-'))],
    ['label' => 'Starts', 'render' => static fn (array $row): string => ui_escape(ui_format_datetime($row['preferred_starts_at'] ?? null))],
    ['label' => 'Status', 'render' => static fn (array $row): string => '<span class="' . ui_escape(ui_status_class($row['status'] ?? '')) . '">' . ui_escape((string) ($row['status'] ?? '')) . '</span>'],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/requests/' . ($row['id'] ?? ''), 'label' => 'Details'],
    ['href' => '/requests/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/requests/' . ($row['id'] ?? '') . '/review', 'label' => 'Review', 'class' => 'button-muted'],
    ['href' => '/requests/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this request?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
