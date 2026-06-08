<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Create Room', 'rooms'); ?>
<div class="card">
<form method="post" action="/rooms" class="stack">
    <div class="fields">
        <div class="field"><label>Floor</label>
            <select name="floor_id" required>
                <option value="">Select floor</option>
                <?php foreach ($floors ?? [] as $floor): $floor = is_object($floor) ? $floor->to_array() : $floor; ?>
                <option value="<?= e($floor['id'] ?? '') ?>"><?= e($floor['name'] ?? '') ?> (level <?= e($floor['level'] ?? '') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Name</label><input type="text" name="name" required></div>
        <div class="field"><label>Position Code</label><input type="text" name="position_code"></div>
        <div class="field"><label>Capacity</label><input type="number" name="capacity" value="30"></div>
        <div class="field"><label>Type</label>
            <select name="room_type"><option value="physical">Physical</option><option value="online">Online</option><option value="hybrid">Hybrid</option></select>
        </div>
        <div class="field"><label>Size</label>
            <select name="room_size"><option value="small">Small</option><option value="medium">Medium</option><option value="large">Large</option></select>
        </div>
    </div>
    <button type="submit">Create Room</button>
</form>
</div>
<?php page_end(); ?>
