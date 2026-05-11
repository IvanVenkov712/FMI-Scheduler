<?php
require_once __DIR__ . '/../shared/ui.php';
$u = $user ?? $current_user ?? [];
$u = is_object($u) ? $u->to_array() : $u;
$evs = $events ?? [];
page_start('My Profile', 'users');
?>
<div class="grid-2">
    <div class="card">
        <h2>Profile</h2>
        <dl class="meta">
            <div><dt>Username</dt><dd><?= e($u['username'] ?? '-') ?></dd></div>
            <div><dt>Email</dt><dd><?= e($u['email'] ?? '-') ?></dd></div>
            <div><dt>Role</dt><dd><?= e($u['role'] ?? $current_user_role ?? '-') ?></dd></div>
            <div><dt>Name</dt><dd><?= e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: '-') ?></dd></div>
            <div><dt>Status</dt><dd><?= status_badge(($u['is_active'] ?? 1) ? 'active' : 'inactive') ?></dd></div>
        </dl>
    </div>
    <div class="card">
        <h2>My Events</h2>
        <?php if (empty($evs)): ?><div class="empty">No events assigned.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>Name</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($evs as $ev): $ev = is_object($ev) ? $ev->to_array() : $ev; ?>
            <tr>
                <td><a href="/events/<?= e($ev['id']) ?>"><?= e($ev['name'] ?? '-') ?></a></td>
                <td><?= fmt_dt($ev['start_time'] ?? null) ?></td>
                <td><?= status_badge($ev['status'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php page_end(); ?>
