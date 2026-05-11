<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Requests', 'requests'); ?>
<div class="actions" style="margin-bottom:16px;"><a href="/requests/create" class="btn">New Request</a></div>
<div class="card">
<?php $items = $events ?? $approvable_events ?? []; ?>
<?php if (empty($items)): ?><div class="empty">No requests found.</div>
<?php else: ?>
<table>
<thead><tr><th>Name</th><th>Subject</th><th>Students</th><th>Mode</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($items as $ev): $ev = is_object($ev) ? $ev->to_array() : $ev; ?>
<tr>
    <td><?= e($ev['name'] ?? '-') ?></td>
    <td><?= e($ev['subject_name'] ?? '-') ?></td>
    <td><?= e($ev['student_count'] ?? '-') ?></td>
    <td><?= e($ev['delivery_mode'] ?? '-') ?></td>
    <td><?= status_badge($ev['status'] ?? '') ?></td>
    <td class="actions">
        <a href="/events/<?= e($ev['id']) ?>" class="btn btn-muted btn-sm">View</a>
        <a href="/requests/<?= e($ev['id']) ?>/review" class="btn btn-muted btn-sm">Review</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
