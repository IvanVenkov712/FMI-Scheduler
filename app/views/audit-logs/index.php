<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Audit Log', 'audit-logs'); ?>

<div class="card">
    <h2>Filters</h2>
    <form method="get" action="/audit-logs" class="stack">
        <div class="fields">
            <div class="field"><label>Action</label><input type="text" name="action" value="<?= e($filters['action'] ?? '') ?>" placeholder="e.g. EventController.approve"></div>
            <div class="field"><label>Username</label><input type="text" name="username" value="<?= e($filters['username'] ?? '') ?>"></div>
            <div class="field"><label>From</label><input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
            <div class="field"><label>To</label><input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
        </div>
        <div class="actions">
            <button type="submit">Filter</button>
            <a href="/audit-logs" class="btn btn-muted">Clear</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Recent activity</h2>
    <?php if (empty($audit_logs)): ?>
        <div class="empty">No audit entries found.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Target</th><th>Request</th><th>Details</th></tr></thead>
        <tbody>
        <?php foreach ($audit_logs as $log): ?>
        <tr>
            <td><?= fmt_dt($log->created_at) ?></td>
            <td><?= e($log->username ?? ($log->user_id ? '#' . $log->user_id : 'System')) ?></td>
            <td><strong><?= e($log->action) ?></strong></td>
            <td><?= e($log->entity_type ?? '-') ?><?= $log->entity_id !== null ? ' #' . e($log->entity_id) : '' ?></td>
            <td><span class="badge neutral"><?= e($log->method) ?></span> <?= e($log->path) ?></td>
            <td>
                <?php if ($log->details): ?>
                <details><summary>View</summary><pre class="audit-details"><?= e(json_encode(json_decode($log->details, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre></details>
                <?php else: ?>-<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php page_end(); ?>
