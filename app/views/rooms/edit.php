<?php
require_once __DIR__ . '/../shared/ui.php';
$r = is_object($room ?? null) ? $room->to_array() : ($room ?? []);
page_start('Edit Room', 'rooms');
?>
<div class="card">
<form method="post" action="/rooms/<?= e($r['id'] ?? '') ?>/update" class="stack">
    <div class="fields">
        <div class="field"><label>Floor</label>
            <select name="floor_id" required>
                <option value="">Select floor</option>
                <?php foreach ($floors ?? [] as $floor): $floor = is_object($floor) ? $floor->to_array() : $floor; ?>
                <option value="<?= e($floor['id'] ?? '') ?>" <?= (string) ($r['floor_id'] ?? '') === (string) ($floor['id'] ?? '') ? 'selected' : '' ?>><?= e($floor['name'] ?? '') ?> (level <?= e($floor['level'] ?? '') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($r['name'] ?? '') ?>" maxlength="100" required></div>
        <div class="field"><label>Position Code</label><input type="text" name="position_code" value="<?= e($r['position_code'] ?? '') ?>" maxlength="50"></div>
        <div class="field"><label>Capacity</label><input type="number" name="capacity" value="<?= e($r['capacity'] ?? 30) ?>" min="0" step="1"></div>
        <div class="field"><label>Type</label>
            <select name="room_type">
                <?php foreach (['physical','online','hybrid'] as $t): ?>
                <option value="<?= $t ?>" <?= ($r['room_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Size</label>
            <select name="room_size">
                <?php foreach (['small','medium','large'] as $s): ?>
                <option value="<?= $s ?>" <?= ($r['room_size'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit">Save</button>
</form>
</div>
<?php page_end(); ?>
