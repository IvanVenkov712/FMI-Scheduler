<?php
declare(strict_types=1);

$roles = isset($roles) && is_array($roles) ? $roles : [];
$pageTitle = 'Create User';
$pageSubtitle = 'Add a new account with role, contact info, and authentication fields.';
$activeNav = 'users';
$formAction = '/users';
$submitLabel = 'Create User';
$fields = [
    ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'placeholder' => 'Select role', 'options' => array_map(static fn (array $row): array => [
        'value' => (string) ($row['id'] ?? ''),
        'label' => (string) ($row['name'] ?? $row['slug'] ?? ''),
    ], $roles)],
    ['name' => 'username', 'label' => 'Username'],
    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
    ['name' => 'first_name', 'label' => 'First Name'],
    ['name' => 'last_name', 'label' => 'Last Name'],
    ['name' => 'phone', 'label' => 'Phone'],
    ['name' => 'faculty_number', 'label' => 'Faculty Number'],
    ['name' => 'department', 'label' => 'Department'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'password', 'label' => 'Password', 'type' => 'password'],
];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
