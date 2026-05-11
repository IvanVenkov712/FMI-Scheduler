<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Events', 'events'); ?>
<div class="actions" style="margin-bottom:16px;">
    <a href="/events/create" class="btn">New Event</a>
    <a href="/exam-sessions/create" class="btn btn-muted">New Exam Session</a>
</div>
<div class="card">
<?php if (empty($events)): ?><div class="empty">No events found.</div>
<?php else: ?>
<table>
<thead><tr><th>Name</th><th>Type</th><th>Mode</th><th>Students</th><th>Start</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($events as $ev): $ev = is_object($ev) ? $ev->to_array() : $ev; ?>
<tr>
    <td><a href="/events/<?= e($ev['id']) ?>"><?= e($ev['name'] ?? $ev['subject_name'] ?? '-') ?></a></td>
    <td><?= e($ev['type'] ?? '-') ?></td>
    <td><?= e($ev['delivery_mode'] ?? '-') ?></td>
    <td><?= e($ev['student_count'] ?? '-') ?></td>
    <td><?= fmt_dt($ev['start_time'] ?? null) ?></td>
    <td><?= status_badge($ev['status'] ?? '') ?></td>
    <td class="actions">
        <a href="/events/<?= e($ev['id']) ?>" class="btn btn-muted btn-sm">View</a>
        <a href="/events/<?= e($ev['id']) ?>/edit" class="btn btn-muted btn-sm">Edit</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
