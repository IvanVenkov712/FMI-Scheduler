<?php
require_once __DIR__ . '/../shared/ui.php';
$s = is_object($schedule ?? null) ? $schedule->to_array() : ($schedule ?? []);
$evs = $events ?? [];
$available_events = $publishable_events ?? [];
$can_manage = current_user_has_role(['admin', 'schedule_manager']);
page_start($s['name'] ?? 'Schedule', 'schedules');
?>
<div class="actions page-actions">
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
        <?php if ($can_manage): ?>
        <form method="post" action="/schedules/<?= e($s['id'] ?? '') ?>/publish-event" class="stack">
            <div class="field"><label>Add event</label>
                <select name="event_id" required>
                    <option value="">Select event</option>
                    <?php foreach ($available_events as $available): $available = is_object($available) ? $available->to_array() : $available; ?>
                    <option value="<?= e($available['id'] ?? '') ?>"><?= e($available['name'] ?? $available['subject_name'] ?? '') ?> - <?= fmt_dt($available['start_time'] ?? null) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" <?= empty($available_events) ? 'disabled' : '' ?>>Add Event</button>
            <?php if (empty($available_events)): ?><div class="empty">No pending or approved events within this schedule period are available.</div><?php endif; ?>
        </form>
        <?php endif; ?>
        <?php if (empty($evs)): ?><div class="empty">No events in this schedule.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>Name</th><th>Time</th><th>Status</th><?php if ($can_manage): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($evs as $ev): $ev = is_object($ev) ? $ev->to_array() : $ev; ?>
            <tr>
                <td><a href="/events/<?= e($ev['id']) ?>"><?= e($ev['name'] ?? $ev['subject_name'] ?? '-') ?></a></td>
                <td><?= fmt_dt($ev['start_time'] ?? null) ?></td>
                <td><?= status_badge($ev['status'] ?? '') ?></td>
                <?php if ($can_manage): ?>
                <td class="actions">
                    <form method="post" action="/schedules/<?= e($s['id'] ?? '') ?>/remove-event" data-confirm="Remove event from schedule?">
                        <input type="hidden" name="event_id" value="<?= e($ev['id'] ?? '') ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php page_end(); ?>
