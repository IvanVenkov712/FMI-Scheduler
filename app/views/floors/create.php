<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Create Floor', 'floors'); ?>
<div class="card">
<form method="post" action="/floors" class="stack">
    <div class="fields">
        <div class="field"><label>Building ID</label><input type="number" name="building_id" required></div>
        <div class="field"><label>Name</label><input type="text" name="name" required></div>
        <div class="field"><label>Floor Number</label><input type="number" name="floor_number" value="0"></div>
        <div class="field"><label>Active</label><select name="is_active"><option value="1">Yes</option><option value="0">No</option></select></div>
    </div>
    <div class="field full"><label>ASCII Plan</label><textarea name="ascii_plan" rows="10" class="ascii-input"></textarea></div>
    <button type="submit">Create Floor</button>
</form>
</div>
<?php page_end(); ?>
