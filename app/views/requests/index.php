<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Requests', 'requests'); ?>
<?php
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
$room_options = [];
foreach ($rooms ?? [] as $room) {
    $room = is_object($room) ? $room->to_array() : $room;
    $room_options[(string) ($room['id'] ?? '')] = $room;
}
$room_label = static function ($room_id) use ($room_options, $floor_label) {
    if ($room_id === null || $room_id === '') {
        return 'Online';
    }

    $room = $room_options[(string) $room_id] ?? null;
    if ($room === null) {
        return 'Room #' . $room_id;
    }

    return ($room['name'] ?? 'Room #' . $room_id)
        . (!empty($room['position_code']) ? ' (' . $room['position_code'] . ')' : '')
        . ' / ' . $floor_label($room['floor_id'] ?? '');
};
?>
<div class="actions page-actions"><a href="/requests/create" class="btn">New Request</a></div>
<div class="card">
    <h2>Filters</h2>
    <form method="get" action="/requests" class="stack">
        <div class="fields">
            <div class="field"><label>Search</label><input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Request, subject, room, building"></div>
            <div class="field"><label>Name or Subject</label><input type="text" name="name" value="<?= e($filters['name'] ?? '') ?>"></div>
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
            <div class="field"><label>Room</label>
                <select name="room_id">
                    <option value="">Any room</option>
                    <?php foreach ($room_options as $room): ?>
                        <option value="<?= e($room['id'] ?? '') ?>"<?= selected_attr($room['id'] ?? '', $filters['room_id'] ?? '') ?>><?= e($room_label($room['id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Mode</label>
                <select name="delivery_mode">
                    <option value="">Any mode</option>
                    <option value="physical"<?= selected_attr('physical', $filters['delivery_mode'] ?? '') ?>>Physical</option>
                    <option value="online"<?= selected_attr('online', $filters['delivery_mode'] ?? '') ?>>Online</option>
                    <option value="hybrid"<?= selected_attr('hybrid', $filters['delivery_mode'] ?? '') ?>>Hybrid</option>
                </select>
            </div>
            <div class="field"><label>Status</label>
                <select name="status">
                    <option value="">Any status</option>
                    <option value="pending"<?= selected_attr('pending', $filters['status'] ?? '') ?>>Pending</option>
                    <option value="approved"<?= selected_attr('approved', $filters['status'] ?? '') ?>>Approved</option>
                    <option value="rejected"<?= selected_attr('rejected', $filters['status'] ?? '') ?>>Rejected</option>
                    <option value="cancelled"<?= selected_attr('cancelled', $filters['status'] ?? '') ?>>Cancelled</option>
                </select>
            </div>
            <div class="field"><label>From</label><input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
            <div class="field"><label>To</label><input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
        </div>
        <div class="actions">
            <button type="submit">Filter</button>
            <a href="/requests" class="btn btn-muted">Clear</a>
        </div>
    </form>
</div>
<div class="card">
<?php $items = $events ?? $approvable_events ?? []; ?>
<?php if (empty($items)): ?><div class="empty">No requests found.</div>
<?php else: ?>
<table>
<thead><tr><th>Name</th><th>Subject</th><th>Students</th><th>Mode</th><th>Room</th><th>Start</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($items as $ev): $ev = is_object($ev) ? $ev->to_array() : $ev; ?>
<tr>
    <td><?= e($ev['name'] ?? '-') ?></td>
    <td><?= e($ev['subject_name'] ?? '-') ?></td>
    <td><?= e($ev['student_count'] ?? '-') ?></td>
    <td><?= e($ev['delivery_mode'] ?? '-') ?></td>
    <td><?= e($room_label($ev['room_id'] ?? null)) ?></td>
    <td><?= fmt_dt($ev['start_time'] ?? null) ?></td>
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
