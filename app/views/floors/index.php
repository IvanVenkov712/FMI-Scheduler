<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Floors', 'floors'); ?>
<div class="actions page-actions"><a href="/floors/create" class="btn">New Floor</a></div>
<div class="card">
<?php if (empty($floors)): ?><div class="empty">No floors found.</div>
<?php else: ?>
<table>
<thead><tr><th>ID</th><th>Building</th><th>Name</th><th>Rooms</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($floors as $f): $f = is_object($f) ? $f->to_array() : $f; ?>
<tr>
    <td><?= e($f['id']) ?></td>
    <td><?= e($f['building_id'] ?? '-') ?></td>
    <td><a href="/floors/<?= e($f['id']) ?>"><?= e($f['name'] ?? 'Floor '.$f['id']) ?></a></td>
    <td><a href="/floors/<?= e($f['id']) ?>/rooms">View Rooms</a></td>
    <td><?= status_badge(($f['is_active'] ?? 1) ? 'active' : 'inactive') ?></td>
    <td class="actions">
        <a href="/floors/<?= e($f['id']) ?>/edit" class="btn btn-muted btn-sm">Edit</a>
        <form method="post" action="/floors/<?= e($f['id']) ?>/delete" data-confirm="Delete?"><button class="btn btn-danger btn-sm">Del</button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
