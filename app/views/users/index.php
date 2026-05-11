<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Users', 'users'); ?>
<div class="card">
<?php if (empty($users)): ?><div class="empty">No users found.</div>
<?php else: ?>
<table>
<thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($users as $u): $u = is_object($u) ? $u->to_array() : $u; ?>
<tr>
    <td><?= e($u['username'] ?? '-') ?></td>
    <td><?= e($u['email'] ?? '-') ?></td>
    <td><?= e($u['role'] ?? '-') ?></td>
    <td><?= status_badge(($u['is_active'] ?? 1) ? 'active' : 'inactive') ?></td>
    <td><a href="/users/<?= e($u['id']) ?>" class="btn btn-muted btn-sm">View</a></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
