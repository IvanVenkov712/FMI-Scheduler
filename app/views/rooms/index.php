<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Rooms', 'rooms'); ?>
<div class="actions page-actions"><a href="/rooms/create" class="btn">New Room</a></div>
<div class="card">
<?php if (empty($rooms)): ?><div class="empty">No rooms found.</div>
<?php else: ?>
<table>
<thead><tr><th>Name</th><th>Code</th><th>Floor</th><th>Type</th><th>Capacity</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($rooms as $r): $r = is_object($r) ? $r->to_array() : $r; ?>
<tr>
    <td><a href="/rooms/<?= e($r['id']) ?>"><?= e($r['name']) ?></a></td>
    <td><?= e($r['position_code'] ?? '-') ?></td>
    <td><?= e($r['floor_id'] ?? '-') ?></td>
    <td><?= e($r['room_type'] ?? '-') ?></td>
    <td><?= e($r['capacity'] ?? '-') ?></td>
    <td><?= status_badge(($r['is_active'] ?? 1) ? 'active' : 'inactive') ?></td>
    <td class="actions">
        <a href="/rooms/<?= e($r['id']) ?>" class="btn btn-muted btn-sm">View</a>
        <a href="/rooms/<?= e($r['id']) ?>/edit" class="btn btn-muted btn-sm">Edit</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
