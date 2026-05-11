<?php
require_once __DIR__ . '/../shared/ui.php';
$r = is_object($route_segment ?? null) ? $route_segment->to_array() : ($route_segment ?? []);
page_start('Route Segment #' . ($r['id'] ?? ''), 'routes');
?>
<div class="actions" style="margin-bottom:16px;"><a href="/routes" class="btn btn-muted">Back</a></div>
<div class="card">
    <dl class="meta">
        <div><dt>From Room</dt><dd><?= e($r['from_room_id'] ?? '-') ?></dd></div>
        <div><dt>To Room</dt><dd><?= e($r['to_room_id'] ?? '-') ?></dd></div>
        <div><dt>Distance</dt><dd><?= e($r['distance_meters'] ?? '-') ?> m</dd></div>
        <div><dt>Walking</dt><dd><?= e($r['walking_minutes'] ?? '-') ?> min</dd></div>
        <div><dt>Accessible</dt><dd><?= ($r['is_accessible'] ?? 0) ? 'Yes' : 'No' ?></dd></div>
        <div><dt>Bidirectional</dt><dd><?= ($r['is_bidirectional'] ?? 0) ? 'Yes' : 'No' ?></dd></div>
    </dl>
</div>
<?php page_end(); ?>
