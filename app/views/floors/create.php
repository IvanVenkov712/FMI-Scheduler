<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Create Floor', 'floors'); ?>
<div class="card">
<form method="post" action="/floors" class="stack">
    <div class="fields">
        <div class="field"><label>Building ID</label><input type="number" name="building_id" required></div>
        <div class="field"><label>Name</label><input type="text" name="name" required></div>
        <div class="field"><label>Level</label><input type="number" name="level" value="0" required></div>
    </div>
    <div class="field full"><label>ASCII Plan</label><textarea name="ascii_plan" rows="10" class="ascii-input"></textarea></div>
    <button type="submit">Create Floor</button>
</form>
</div>
<?php page_end(); ?>
