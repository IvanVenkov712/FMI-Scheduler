<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Schedules', 'schedules'); ?>
<div class="actions page-actions"><a href="/schedules/create" class="btn">New Schedule</a></div>
<div class="card">
<?php if (empty($schedules)): ?><div class="empty">No schedules yet.</div>
<?php else: ?>
<table>
<thead><tr><th>Name</th><th>Type</th><th>Period</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($schedules as $s): $s = is_object($s) ? $s->to_array() : $s; ?>
<tr>
    <td><a href="/schedules/<?= e($s['id']) ?>"><?= e($s['name'] ?? '-') ?></a></td>
    <td><?= e($s['type'] ?? '-') ?></td>
    <td><?= fmt_dt($s['start_date'] ?? null) ?> &mdash; <?= fmt_dt($s['end_date'] ?? null) ?></td>
    <td><?= status_badge($s['status'] ?? '') ?></td>
    <td class="actions">
        <a href="/schedules/<?= e($s['id']) ?>/calendar" class="btn btn-muted btn-sm">Calendar</a>
        <a href="/schedules/<?= e($s['id']) ?>" class="btn btn-muted btn-sm">View</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
