<?php
declare(strict_types=1);

$users = isset($users) && is_array($users) ? $users : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$pageTitle = 'Users';
$pageSubtitle = 'Manage accounts, roles, and visible identity data for system users.';
$activeNav = 'users';
$items = $users;
$actions = [['href' => '/users/create', 'label' => 'New User']];
$filterFields = [
    ['name' => 'role_id', 'label' => 'Role ID', 'type' => 'number'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'department', 'label' => 'Department', 'type' => 'text'],
];
$columns = [
    ['label' => 'Name', 'render' => static fn (array $row): string => '<a href="/users/' . ui_escape((string) ($row['id'] ?? '')) . '">' . ui_escape(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) . '</a>'],
    ['label' => 'Username', 'render' => static fn (array $row): string => ui_escape((string) ($row['username'] ?? '-'))],
    ['label' => 'Email', 'render' => static fn (array $row): string => ui_escape((string) ($row['email'] ?? '-'))],
    ['label' => 'Role', 'render' => static fn (array $row): string => ui_escape((string) ($row['role_name'] ?? $row['role_id'] ?? '-'))],
    ['label' => 'Status', 'render' => static fn (array $row): string => '<span class="' . ui_escape(ui_status_class($row['status'] ?? '')) . '">' . ui_escape((string) ($row['status'] ?? '')) . '</span>'],
];
$columnActions = static fn (array $row): array => [
    ['href' => '/users/' . ($row['id'] ?? ''), 'label' => 'Profile'],
    ['href' => '/users/' . ($row['id'] ?? '') . '/edit', 'label' => 'Edit', 'class' => 'button-muted'],
    ['href' => '/users/' . ($row['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this user?'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-index.php';
