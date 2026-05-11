<?php
require_once __DIR__ . '/../shared/ui.php';
$r = is_object($room ?? null) ? $room->to_array() : ($room ?? []);
page_start($r['name'] ?? 'Room Details', 'rooms');
?>
<div class="actions" style="margin-bottom:16px;">
    <a href="/rooms/<?= e($r['id']) ?>/edit" class="btn">Edit</a>
    <a href="/rooms" class="btn btn-muted">Back</a>
</div>
<div class="card">
    <h2>Room Info</h2>
    <dl class="meta">
        <div><dt>Name</dt><dd><?= e($r['name'] ?? '-') ?></dd></div>
        <div><dt>Code</dt><dd><?= e($r['position_code'] ?? '-') ?></dd></div>
        <div><dt>Floor ID</dt><dd><?= e($r['floor_id'] ?? '-') ?></dd></div>
        <div><dt>Type</dt><dd><?= e($r['room_type'] ?? '-') ?></dd></div>
        <div><dt>Capacity</dt><dd><?= e($r['capacity'] ?? '-') ?></dd></div>
        <div><dt>Size</dt><dd><?= e($r['room_size'] ?? '-') ?></dd></div>
        <div><dt>Status</dt><dd><?= status_badge(($r['is_active'] ?? 1) ? 'active' : 'inactive') ?></dd></div>
    </dl>
</div>
<?php page_end(); ?>
