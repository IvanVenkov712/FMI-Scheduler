<?php
require_once __DIR__ . '/../shared/ui.php';
$f = is_object($floor ?? null) ? $floor->to_array() : ($floor ?? []);
page_start('Edit Floor', 'floors');
?>
<div class="card">
<form method="post" action="/floors/<?= e($f['id'] ?? '') ?>/update" class="stack">
    <div class="fields">
        <div class="field"><label>Building ID</label><input type="number" name="building_id" value="<?= e($f['building_id'] ?? '') ?>" required></div>
        <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($f['name'] ?? '') ?>" required></div>
        <div class="field"><label>Level</label><input type="number" name="level" value="<?= e($f['level'] ?? 0) ?>" required></div>
    </div>
    <div class="field full"><label>ASCII Plan</label><textarea name="ascii_plan" rows="10" class="ascii-input"><?= e($f['ascii_plan'] ?? '') ?></textarea></div>
    <button type="submit">Save</button>
</form>
</div>
<?php page_end(); ?>
