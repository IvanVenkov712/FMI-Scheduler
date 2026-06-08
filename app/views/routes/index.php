<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Routes', 'routes'); ?>
<div class="actions page-actions">
    <a href="/routes/map" class="btn btn-muted">Map View</a>
</div>
<div class="card">
    <h2>Add Route Segment</h2>
    <form method="post" action="/routes" class="stack">
        <div class="fields">
            <div class="field"><label>From Room</label>
                <select name="from_room_id" required>
                    <option value="">Select room</option>
                    <?php foreach ($rooms ?? [] as $room): $room = is_object($room) ? $room->to_array() : $room; ?>
                    <option value="<?= e($room['id'] ?? '') ?>"><?= e($room['name'] ?? '') ?><?= !empty($room['position_code']) ? ' (' . e($room['position_code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>To Room</label>
                <select name="to_room_id" required>
                    <option value="">Select room</option>
                    <?php foreach ($rooms ?? [] as $room): $room = is_object($room) ? $room->to_array() : $room; ?>
                    <option value="<?= e($room['id'] ?? '') ?>"><?= e($room['name'] ?? '') ?><?= !empty($room['position_code']) ? ' (' . e($room['position_code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Distance (m)</label><input type="number" name="distance" min="0" step="0.1"></div>
            <div class="field"><label>Corridor Capacity</label><input type="number" name="corridor_capacity" min="0"></div>
            <div class="field"><label>Stairs</label><select name="is_stairs"><option value="0">No</option><option value="1">Yes</option></select></div>
            <div class="field"><label>Elevator</label><select name="is_elevator"><option value="0">No</option><option value="1">Yes</option></select></div>
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
        <thead><tr><th>ID</th><th>From</th><th>To</th><th>Distance</th><th>Capacity</th><th>Access</th></tr></thead>
        <tbody>
        <?php foreach ($items as $r): $r = is_object($r) ? $r->to_array() : $r; ?>
        <tr>
            <td><a href="/routes/<?= e($r['id']) ?>"><?= e($r['id']) ?></a></td>
            <td><?= e($r['from_room_id'] ?? '-') ?></td>
            <td><?= e($r['to_room_id'] ?? '-') ?></td>
            <td><?= e($r['distance'] ?? '-') ?>m</td>
            <td><?= e($r['corridor_capacity'] ?? '-') ?></td>
            <td><?= ($r['is_stairs'] ?? 0) ? 'Stairs' : (($r['is_elevator'] ?? 0) ? 'Elevator' : 'Level') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php page_end(); ?>
