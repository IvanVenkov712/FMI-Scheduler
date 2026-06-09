<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Routes', 'routes'); ?>
<?php
$room_options = [];
foreach ($rooms ?? [] as $room) {
    $room = is_object($room) ? $room->to_array() : $room;
    $room_options[(string) ($room['id'] ?? '')] = $room;
}
$room_label = static function ($room_id) use ($room_options) {
    $room = $room_options[(string) $room_id] ?? null;
    if ($room === null) {
        return 'Room #' . $room_id;
    }

    return ($room['name'] ?? 'Room #' . $room_id)
        . (!empty($room['position_code']) ? ' (' . $room['position_code'] . ')' : '');
};
$search = $route_search ?? [];
?>
<div class="actions page-actions">
    <a href="#find-route" class="btn">Find Route</a>
    <a href="#add-segment" class="btn btn-muted">Add Segment</a>
</div>
<div class="card" id="find-route">
    <h2>Find Route Between Rooms</h2>
    <form method="post" action="/routes/find" class="stack">
        <div class="fields">
            <div class="field"><label>From Room</label>
                <select name="from_room_id" required>
                    <option value="">Select room</option>
                    <?php foreach ($room_options as $room): ?>
                    <option value="<?= e($room['id'] ?? '') ?>" <?= (string) ($search['from_room_id'] ?? '') === (string) ($room['id'] ?? '') ? 'selected' : '' ?>><?= e($room_label($room['id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>To Room</label>
                <select name="to_room_id" required>
                    <option value="">Select room</option>
                    <?php foreach ($room_options as $room): ?>
                    <option value="<?= e($room['id'] ?? '') ?>" <?= (string) ($search['to_room_id'] ?? '') === (string) ($room['id'] ?? '') ? 'selected' : '' ?>><?= e($room_label($room['id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Start Time <small>(optional)</small></label><input type="datetime-local" name="start_time" value="<?= e($search['start_time'] ?? '') ?>"></div>
            <div class="field"><label>End Time <small>(optional)</small></label><input type="datetime-local" name="end_time" value="<?= e($search['end_time'] ?? '') ?>"></div>
            <div class="field full checkbox-field">
                <label><input type="checkbox" name="accessible_only" value="1" <?= !empty($search['accessible_only']) ? 'checked' : '' ?>> Accessible route only (elevators, no stairs)</label>
            </div>
        </div>
        <button type="submit">Find Route</button>
    </form>
</div>
<?php if (isset($route_search)): ?>
<div class="card">
    <h2>Route Result</h2>
    <?php if (!empty($route_search_error)): ?>
        <div class="alert err"><?= e($route_search_error) ?></div>
    <?php elseif (empty($route_result)): ?>
        <div class="alert ok">You are already at the destination. Total distance: 0m.</div>
    <?php else: ?>
        <div class="route-summary">
            <strong><?= e($room_label($search['from_room_id'] ?? '')) ?></strong>
            <span>to</span>
            <strong><?= e($room_label($search['to_room_id'] ?? '')) ?></strong>
            <span class="badge ok"><?= e($route_distance ?? 0) ?>m total</span>
        </div>
        <ol class="route-steps">
        <?php foreach ($route_result as $segment): $segment = is_object($segment) ? $segment->to_array() : $segment; ?>
            <li>
                <strong><?= e($room_label($segment['from_room_id'] ?? '')) ?></strong>
                <span>to <?= e($room_label($segment['to_room_id'] ?? '')) ?></span>
                <span class="badge neutral"><?= e($segment['distance'] ?? 0) ?>m</span>
                <?php if (!empty($segment['is_elevator'])): ?><span class="badge ok">Elevator</span><?php endif; ?>
                <?php if (!empty($segment['is_stairs'])): ?><span class="badge warn">Stairs</span><?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
<?php endif; ?>
<div class="card" id="add-segment">
    <h2>Add Route Segment</h2>
    <form method="post" action="/routes" class="stack">
        <div class="fields">
            <div class="field"><label>From Room</label>
                <select name="from_room_id" required>
                    <option value="">Select room</option>
                    <?php foreach ($room_options as $room): ?>
                    <option value="<?= e($room['id'] ?? '') ?>"><?= e($room_label($room['id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>To Room</label>
                <select name="to_room_id" required>
                    <option value="">Select room</option>
                    <?php foreach ($room_options as $room): ?>
                    <option value="<?= e($room['id'] ?? '') ?>"><?= e($room_label($room['id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Distance (m)</label><input type="number" name="distance" min="0" step="0.1"></div>
            <div class="field"><label>Corridor Capacity</label><input type="number" name="corridor_capacity" min="0" step="1"></div>
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
            <td><?= e($room_label($r['from_room_id'] ?? '-')) ?></td>
            <td><?= e($room_label($r['to_room_id'] ?? '-')) ?></td>
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
