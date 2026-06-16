<?php
require_once __DIR__ . '/../shared/ui.php';
$b = is_object($building ?? null) ? $building->to_array() : ($building ?? []);
page_start('Edit Building', 'buildings');
?>
<div class="card">
    <form method="post" action="/buildings/<?= e($b['id'] ?? '') ?>/update" class="stack">
        <div class="fields">
            <div class="field"><label>Code</label><input type="text" name="code" value="<?= e($b['code'] ?? '') ?>" maxlength="50" required></div>
            <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($b['name'] ?? '') ?>" maxlength="150" required></div>
            <div class="field"><label>Address</label><input type="text" name="address" value="<?= e($b['address'] ?? '') ?>"></div>
            <div class="field"><label>Campus Area</label><input type="text" name="campus_area" value="<?= e($b['campus_area'] ?? '') ?>"></div>
            <div class="field"><label>Floor Count</label><input type="number" name="floor_count" value="<?= e($b['floor_count'] ?? 1) ?>" min="0" step="1"></div>
            <div class="field"><label>Active</label><select name="is_active"><option value="1" <?= ($b['is_active'] ?? 1) ? 'selected' : '' ?>>Yes</option><option value="0" <?= !($b['is_active'] ?? 1) ? 'selected' : '' ?>>No</option></select></div>
        </div>
        <button type="submit">Save</button>
    </form>
</div>
<?php page_end(); ?>
