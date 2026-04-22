<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$user = isset($user) && is_array($user) ? $user : [];
$pageTitle = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
$pageTitle = $pageTitle !== '' ? $pageTitle : 'User Profile';
$pageSubtitle = 'Public account information used for system access and role-aware features.';
$activeNav = 'users';
$summary = [
    'Username' => ui_escape((string) ($user['username'] ?? '-')),
    'Email' => ui_escape((string) ($user['email'] ?? '-')),
    'Role' => ui_escape((string) ($user['role_name'] ?? $user['role_id'] ?? '-')),
    'Department' => ui_escape((string) ($user['department'] ?? '-')),
    'Phone' => ui_escape((string) ($user['phone'] ?? '-')),
    'Faculty Number' => ui_escape((string) ($user['faculty_number'] ?? '-')),
    'Status' => '<span class="' . ui_escape(ui_status_class($user['status'] ?? '')) . '">' . ui_escape((string) ($user['status'] ?? '-')) . '</span>',
];
$actions = isset($user['id']) ? [
    ['href' => '/users/' . ($user['id'] ?? '') . '/edit', 'label' => 'Edit User'],
    [
        'href' => '/users/' . ($user['id'] ?? '') . '/reset-password',
        'label' => 'Reset Password',
        'method' => 'POST',
        'class' => 'button-muted',
        'confirm' => 'Generate a new temporary password for this user?',
        'hidden' => ['return_to' => '/users/' . ($user['id'] ?? '')],
    ],
] : [];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-details.php';
