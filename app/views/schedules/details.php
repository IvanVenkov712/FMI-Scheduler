<?php
require_once __DIR__ . '/../shared/ui.php';
$s = is_object($schedule ?? null) ? $schedule->to_array() : ($schedule ?? []);
$evs = $events ?? [];
page_start($s['name'] ?? 'Schedule', 'schedules');
?>
<div class="actions" style="margin-bottom:16px;">
    <a href="/schedules" class="btn btn-muted">Back</a>
</div>
<div class="grid-2">
    <div class="card">
        <h2>Schedule Info</h2>
        <dl class="meta">
            <div><dt>Name</dt><dd><?= e($s['name'] ?? '-') ?></dd></div>
            <div><dt>Type</dt><dd><?= e($s['type'] ?? '-') ?></dd></div>
            <div><dt>Start</dt><dd><?= fmt_dt($s['start_date'] ?? null) ?></dd></div>
            <div><dt>End</dt><dd><?= fmt_dt($s['end_date'] ?? null) ?></dd></div>
            <div><dt>Status</dt><dd><?= status_badge($s['status'] ?? '') ?></dd></div>
        </dl>
    </div>
    <div class="card">
        <h2>Events (<?= count($evs) ?>)</h2>
        <?php if (empty($evs)): ?><div class="empty">No events in this schedule.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>Name</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($evs as $ev): $ev = is_object($ev) ? $ev->to_array() : $ev; ?>
            <tr>
                <td><a href="/events/<?= e($ev['id']) ?>"><?= e($ev['name'] ?? $ev['subject_name'] ?? '-') ?></a></td>
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
