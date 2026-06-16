<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Floors', 'floors'); ?>
<?php
$can_delete_floors = current_user_has_role(['admin', 'schedule_manager']);
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
?>
<div class="actions page-actions"><a href="/floors/create" class="btn">New Floor</a></div>
<div class="card">
    <h2>Filters</h2>
    <form method="get" action="/floors" class="stack">
        <div class="fields">
            <div class="field"><label>Search</label><input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Floor or building"></div>
            <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($filters['name'] ?? '') ?>"></div>
            <div class="field"><label>Building</label>
                <select name="building_id">
                    <option value="">Any building</option>
                    <?php foreach ($building_options as $building): ?>
                        <option value="<?= e($building['id'] ?? '') ?>"<?= selected_attr($building['id'] ?? '', $filters['building_id'] ?? '') ?>><?= e($building_label($building['id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Level</label><input type="number" name="level" value="<?= e($filters['level'] ?? '') ?>" step="1"></div>
        </div>
        <div class="actions">
            <button type="submit">Filter</button>
            <a href="/floors" class="btn btn-muted">Clear</a>
        </div>
    </form>
</div>
<div class="card">
<?php if (empty($floors)): ?><div class="empty">No floors found.</div>
<?php else: ?>
<table>
<thead><tr><th>ID</th><th>Building</th><th>Level</th><th>Name</th><th>Rooms</th><th></th></tr></thead>
<tbody>
<?php foreach ($floors as $f): $f = is_object($f) ? $f->to_array() : $f; ?>
<tr>
    <td><?= e($f['id']) ?></td>
    <td><?= e($building_label($f['building_id'] ?? '-')) ?></td>
    <td><?= e($f['level'] ?? '-') ?></td>
    <td><a href="/floors/<?= e($f['id']) ?>"><?= e($f['name'] ?? 'Floor '.$f['id']) ?></a></td>
    <td><a href="/floors/<?= e($f['id']) ?>/rooms">View Rooms</a></td>
    <td class="actions">
        <a href="/floors/<?= e($f['id']) ?>/plan" class="btn btn-muted btn-sm">Plan</a>
        <a href="/floors/<?= e($f['id']) ?>/edit" class="btn btn-muted btn-sm">Edit</a>
        <?php if ($can_delete_floors): ?>
        <form method="post" action="/floors/<?= e($f['id']) ?>/delete" data-confirm="Delete?"><button class="btn btn-danger btn-sm">Del</button></form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php page_end(); ?>
