<?php
require_once __DIR__ . '/../shared/ui.php';
$u = is_object($user ?? null) ? $user->to_array() : ($user ?? []);
$name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
$can_manage_user = ($current_user_role ?? '') === 'admin'
    && (int) ($u['id'] ?? 0) !== (int) ($current_user_id ?? 0);
$roles = ['admin' => 'Admin', 'schedule_manager' => 'Schedule manager', 'teacher' => 'Teacher', 'student' => 'Student'];
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

<?php if ($can_manage_user): ?>
<div class="card">
    <h2>Manage Access</h2>
    <form class="stack" method="post" action="/users/<?= e($u['id']) ?>/role-status">
        <div class="fields">
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <?php foreach ($roles as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($u['role'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1" <?= ($u['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($u['is_active'] ?? 1) ? '' : 'selected' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="actions">
            <button type="submit">Save</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php page_end(); ?>
