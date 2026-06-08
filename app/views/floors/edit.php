<?php
require_once __DIR__ . '/../shared/ui.php';
$f = is_object($floor ?? null) ? $floor->to_array() : ($floor ?? []);
page_start('Edit Floor', 'floors');
?>
<div class="card">
<form method="post" action="/floors/<?= e($f['id'] ?? '') ?>/update" class="stack">
    <div class="fields">
        <div class="field"><label>Building</label>
            <select name="building_id" required>
                <option value="">Select building</option>
                <?php foreach ($buildings ?? [] as $building): $building = is_object($building) ? $building->to_array() : $building; ?>
                <option value="<?= e($building['id'] ?? '') ?>" <?= (string) ($f['building_id'] ?? '') === (string) ($building['id'] ?? '') ? 'selected' : '' ?>><?= e($building['name'] ?? '') ?><?= !empty($building['code']) ? ' (' . e($building['code']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($f['name'] ?? '') ?>" required></div>
        <div class="field"><label>Level</label><input type="number" name="level" value="<?= e($f['level'] ?? 0) ?>" required></div>
    </div>
    <div class="field full"><label>ASCII Plan</label><textarea name="ascii_plan" rows="10" class="ascii-input"><?= e($f['ascii_plan'] ?? '') ?></textarea></div>
    <button type="submit">Save</button>
</form>
</div>
<?php page_end(); ?>
