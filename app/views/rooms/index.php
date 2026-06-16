<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Rooms', 'rooms'); ?>
<?php
$can_delete_rooms = current_user_has_role('admin');
$building_options = [];
foreach ($buildings ?? [] as $building) {
    $building = is_object($building) ? $building->to_array() : $building;
    $building_options[(string) ($building['id'] ?? '')] = $building;
}
$building_label = static function ($building_id) use ($building_options) {
    $building = $building_options[(string) $building_id] ?? null;

    return $building === null
        ? 'Building #' . $building_id
        : (($building['name'] ?? 'Building #' . $building_id) . (!empty($building['code']) ? ' (' . $building['code'] . ')' : ''));
};
$floor_options = [];
foreach ($floors ?? [] as $floor) {
    $floor = is_object($floor) ? $floor->to_array() : $floor;
    $floor_options[(string) ($floor['id'] ?? '')] = $floor;
}
$floor_label = static function ($floor_id) use ($floor_options, $building_label) {
    $floor = $floor_options[(string) $floor_id] ?? null;
    if ($floor === null) {
        return 'Floor #' . $floor_id;
    }

    $name = trim((string) ($floor['name'] ?? ''));
    $label = $name !== '' ? $name : 'Level ' . ($floor['level'] ?? '-');

    return $building_label($floor['building_id'] ?? '') . ' / ' . $label;
};
?>
<div class="actions page-actions"><a href="/rooms/create" class="btn">New Room</a></div>
<div class="card">
    <h2>Filters</h2>
    <form method="get" action="/rooms" class="stack">
        <div class="fields">
            <div class="field"><label>Search</label><input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Room, code, building, floor"></div>
            <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($filters['name'] ?? '') ?>"></div>
            <div class="field"><label>Building</label>
                <select name="building_id">
                    <option value="">Any building</option>
                    <?php foreach ($building_options as $building): ?>
                        <option value="<?= e($building['id'] ?? '') ?>"<?= selected_attr($building['id'] ?? '', $filters['building_id'] ?? '') ?>><?= e($building_label($building['id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Floor</label>
                <select name="floor_id">
                    <option value="">Any floor</option>
                    <?php foreach ($floor_options as $floor): ?>
                        <option value="<?= e($floor['id'] ?? '') ?>"<?= selected_attr($floor['id'] ?? '', $filters['floor_id'] ?? '') ?>><?= e($floor_label($floor['id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Room Code</label><input type="text" name="position_code" value="<?= e($filters['position_code'] ?? '') ?>"></div>
            <div class="field"><label>Type</label>
                <select name="room_type">
                    <option value="">Any type</option>
                    <option value="physical"<?= selected_attr('physical', $filters['room_type'] ?? '') ?>>Physical</option>
                    <option value="online"<?= selected_attr('online', $filters['room_type'] ?? '') ?>>Online</option>
                    <option value="hybrid"<?= selected_attr('hybrid', $filters['room_type'] ?? '') ?>>Hybrid</option>
                </select>
            </div>
            <div class="field"><label>Size</label>
                <select name="room_size">
                    <option value="">Any size</option>
                    <option value="small"<?= selected_attr('small', $filters['room_size'] ?? '') ?>>Small</option>
                    <option value="medium"<?= selected_attr('medium', $filters['room_size'] ?? '') ?>>Medium</option>
                    <option value="large"<?= selected_attr('large', $filters['room_size'] ?? '') ?>>Large</option>
                </select>
            </div>
            <div class="field"><label>Min Capacity</label><input type="number" name="capacity_min" value="<?= e($filters['capacity_min'] ?? '') ?>" min="0" step="1"></div>
            <div class="field"><label>Status</label>
                <select name="status">
                    <option value="">Any status</option>
                    <option value="active"<?= selected_attr('active', $filters['status'] ?? '') ?>>Active</option>
                    <option value="inactive"<?= selected_attr('inactive', $filters['status'] ?? '') ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="actions">
            <button type="submit">Filter</button>
            <a href="/rooms" class="btn btn-muted">Clear</a>
        </div>
    </form>
</div>
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
    <td><?= e($floor_label($r['floor_id'] ?? '-')) ?></td>
    <td><?= e($r['room_type'] ?? '-') ?></td>
    <td><?= e($r['capacity'] ?? '-') ?></td>
    <td><?= status_badge(($r['is_active'] ?? 1) ? 'active' : 'inactive') ?></td>
    <td class="actions">
        <a href="/rooms/<?= e($r['id']) ?>" class="btn btn-muted btn-sm">View</a>
        <a href="/rooms/<?= e($r['id']) ?>/edit" class="btn btn-muted btn-sm">Edit</a>
        <?php if ($can_delete_rooms): ?>
        <form method="post" action="/rooms/<?= e($r['id']) ?>/delete" data-confirm="Delete this room?"><button class="btn btn-danger btn-sm">Del</button></form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
