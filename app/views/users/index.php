<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Users', 'users'); ?>
<div class="card">
<?php if (empty($users)): ?><div class="empty">No users found.</div>
<?php else: ?>
<?php
$can_manage_users = ($current_user_role ?? '') === 'admin';
$roles = ['admin' => 'Admin', 'schedule_manager' => 'Schedule manager', 'teacher' => 'Teacher', 'student' => 'Student'];
?>
<table>
<thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th><?= $can_manage_users ? 'Manage' : '' ?></th></tr></thead>
<tbody>
<?php foreach ($users as $u): $u = is_object($u) ? $u->to_array() : $u; ?>
<?php $is_current_user = (int) ($u['id'] ?? 0) === (int) ($current_user_id ?? 0); ?>
<tr>
    <td><?= e($u['username'] ?? '-') ?></td>
    <td><?= e($u['email'] ?? '-') ?></td>
    <?php if ($can_manage_users && !$is_current_user): ?>
    <td colspan="3">
        <form class="user-manage-form" method="post" action="/users/<?= e($u['id']) ?>/role-status">
            <select name="role" aria-label="Role for <?= e($u['username'] ?? 'user') ?>">
                <?php foreach ($roles as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($u['role'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="is_active" aria-label="Status for <?= e($u['username'] ?? 'user') ?>">
                <option value="1" <?= ($u['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= ($u['is_active'] ?? 1) ? '' : 'selected' ?>>Inactive</option>
            </select>
            <button type="submit" class="btn-sm">Save</button>
            <a href="/users/<?= e($u['id']) ?>" class="btn btn-muted btn-sm">View</a>
        </form>
    </td>
    <?php else: ?>
    <td><?= e($u['role'] ?? '-') ?></td>
    <td><?= status_badge(($u['is_active'] ?? 1) ? 'active' : 'inactive') ?></td>
    <td><a href="/users/<?= e($u['id']) ?>" class="btn btn-muted btn-sm">View</a></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
