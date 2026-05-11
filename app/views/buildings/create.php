<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Create Building', 'buildings'); ?>
<div class="card">
    <form method="post" action="/buildings" class="stack">
        <div class="fields">
            <div class="field"><label>Code</label><input type="text" name="code" required></div>
            <div class="field"><label>Name</label><input type="text" name="name" required></div>
            <div class="field"><label>Address</label><input type="text" name="address"></div>
            <div class="field"><label>Campus Area</label><input type="text" name="campus_area"></div>
            <div class="field"><label>Floor Count</label><input type="number" name="floor_count" value="1"></div>
            <div class="field"><label>Active</label><select name="is_active"><option value="1">Yes</option><option value="0">No</option></select></div>
        </div>
        <button type="submit">Create Building</button>
    </form>
</div>
<?php page_end(); ?>
