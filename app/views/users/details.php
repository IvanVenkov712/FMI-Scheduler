<?php
require_once __DIR__ . '/../shared/ui.php';
$u = is_object($user ?? null) ? $user->to_array() : ($user ?? []);
$name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
page_start($u['username'] ?? 'User Details', 'users');
?>

<div class="actions page-actions">
    <a href="/users" class="btn btn-muted">Back</a>
</div>

<div class="card">
    <h2>User Info</h2>
    <dl class="meta">
        <div><dt>Username</dt><dd><?= e($u['username'] ?? '-') ?></dd></div>
        <div><dt>Email</dt><dd><?= e($u['email'] ?? '-') ?></dd></div>
        <div><dt>Role</dt><dd><?= e($u['role'] ?? '-') ?></dd></div>
        <div><dt>Name</dt><dd><?= e($name !== '' ? $name : '-') ?></dd></div>
        <div><dt>Status</dt><dd><?= status_badge(($u['is_active'] ?? 1) ? 'active' : 'inactive') ?></dd></div>
    </dl>
</div>

<?php page_end(); ?>
