<?php
require_once __DIR__ . '/../shared/ui.php';
$r = is_object($route_segment ?? null) ? $route_segment->to_array() : ($route_segment ?? []);
page_start('Route Segment #' . ($r['id'] ?? ''), 'routes');
?>
<div class="actions page-actions"><a href="/routes" class="btn btn-muted">Back</a></div>
<div class="card">
    <dl class="meta">
        <div><dt>From Room</dt><dd><?= e($r['from_room_id'] ?? '-') ?></dd></div>
        <div><dt>To Room</dt><dd><?= e($r['to_room_id'] ?? '-') ?></dd></div>
        <div><dt>Distance</dt><dd><?= e($r['distance'] ?? '-') ?> m</dd></div>
        <div><dt>Corridor Capacity</dt><dd><?= e($r['corridor_capacity'] ?? '-') ?></dd></div>
        <div><dt>Stairs</dt><dd><?= ($r['is_stairs'] ?? 0) ? 'Yes' : 'No' ?></dd></div>
        <div><dt>Elevator</dt><dd><?= ($r['is_elevator'] ?? 0) ? 'Yes' : 'No' ?></dd></div>
    </dl>
</div>
<?php page_end(); ?>
