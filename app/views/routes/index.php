<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Routes', 'routes'); ?>
<div class="actions" style="margin-bottom:16px;">
    <a href="/routes/map" class="btn btn-muted">Map View</a>
</div>
<div class="card">
    <h2>Add Route Segment</h2>
    <form method="post" action="/routes" class="stack">
        <div class="fields">
            <div class="field"><label>From Room ID</label><input type="number" name="from_room_id" required></div>
            <div class="field"><label>To Room ID</label><input type="number" name="to_room_id" required></div>
            <div class="field"><label>Distance (m)</label><input type="number" name="distance_meters" step="0.1"></div>
            <div class="field"><label>Walking (min)</label><input type="number" name="walking_minutes" step="0.5"></div>
            <div class="field"><label>Is Accessible</label><select name="is_accessible"><option value="1">Yes</option><option value="0">No</option></select></div>
            <div class="field"><label>Bidirectional</label><select name="is_bidirectional"><option value="1">Yes</option><option value="0">No</option></select></div>
        </div>
        <button type="submit">Add Segment</button>
    </form>
</div>
<div class="card">
    <h2>All Route Segments</h2>
    <?php $items = $route_segments ?? []; ?>
    <?php if (empty($items)): ?><div class="empty">No route segments defined.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>ID</th><th>From</th><th>To</th><th>Distance</th><th>Walking</th><th>Accessible</th></tr></thead>
        <tbody>
        <?php foreach ($items as $r): $r = is_object($r) ? $r->to_array() : $r; ?>
        <tr>
            <td><a href="/routes/<?= e($r['id']) ?>"><?= e($r['id']) ?></a></td>
            <td><?= e($r['from_room_id'] ?? '-') ?></td>
            <td><?= e($r['to_room_id'] ?? '-') ?></td>
            <td><?= e($r['distance_meters'] ?? '-') ?>m</td>
            <td><?= e($r['walking_minutes'] ?? '-') ?>min</td>
            <td><?= ($r['is_accessible'] ?? 0) ? 'Yes' : 'No' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php page_end(); ?>
