<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Schedules', 'schedules'); ?>
<div class="actions page-actions"><a href="/schedules/create" class="btn">New Schedule</a></div>
<div class="card">
    <h2>Filters</h2>
    <form method="get" action="/schedules" class="stack">
        <div class="fields">
            <div class="field"><label>Search</label><input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Name or description"></div>
            <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($filters['name'] ?? '') ?>"></div>
            <div class="field"><label>Period From</label><input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
            <div class="field"><label>Period To</label><input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
        </div>
        <div class="actions">
            <button type="submit">Filter</button>
            <a href="/schedules" class="btn btn-muted">Clear</a>
        </div>
    </form>
</div>
<div class="card">
<?php if (empty($schedules)): ?><div class="empty">No schedules yet.</div>
<?php else: ?>
<table>
<thead><tr><th>Name</th><th>Description</th><th>Period</th><th></th></tr></thead>
<tbody>
<?php foreach ($schedules as $s): $s = is_object($s) ? $s->to_array() : $s; ?>
<tr>
    <td><a href="/schedules/<?= e($s['id']) ?>"><?= e($s['name'] ?? '-') ?></a></td>
    <td><?= e($s['description'] ?? '-') ?></td>
    <td><?= fmt_dt($s['start_date'] ?? null) ?> &mdash; <?= fmt_dt($s['end_date'] ?? null) ?></td>
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
