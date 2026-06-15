<?php
require_once __DIR__ . '/../shared/ui.php';
$ev = is_object($event ?? null) ? $event->to_array() : ($event ?? []);
page_start('Edit Event', 'events');
?>
<div class="card">
<form method="post" action="/events/<?= e($ev['id'] ?? '') ?>/update" class="stack">
    <div class="fields">
        <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($ev['name'] ?? '') ?>" maxlength="150" required></div>
        <div class="field"><label>Subject</label><input type="text" name="subject_name" value="<?= e($ev['subject_name'] ?? '') ?>"></div>
        <div class="field"><label>Type</label>
            <select name="type"><?php foreach (['lecture','exam','practice','consultation'] as $t): ?>
            <option value="<?= $t ?>" <?= ($ev['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?></select>
        </div>
        <div class="field"><label>Students</label><input type="number" name="student_count" value="<?= e($ev['student_count'] ?? 30) ?>" min="0" step="1"></div>
        <div class="field"><label>Start Time</label><input type="datetime-local" name="start_time" value="<?= e(str_replace(' ', 'T', substr($ev['start_time'] ?? '', 0, 16))) ?>" required></div>
        <div class="field"><label>End Time</label><input type="datetime-local" name="end_time" value="<?= e(str_replace(' ', 'T', substr($ev['end_time'] ?? '', 0, 16))) ?>" required></div>
        <div class="field"><label>Delivery Mode</label>
            <select name="delivery_mode"><?php foreach (['physical','online','hybrid'] as $m): ?>
            <option value="<?= $m ?>" <?= ($ev['delivery_mode'] ?? '') === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
            <?php endforeach; ?></select>
        </div>
        <div class="field"><label>Preferred Room</label>
            <select name="room_id">
                <option value="">No preference</option>
                <?php foreach ($rooms ?? [] as $room): $room = is_object($room) ? $room->to_array() : $room; ?>
                <option value="<?= e($room['id'] ?? '') ?>" <?= (string) ($ev['room_id'] ?? '') === (string) ($room['id'] ?? '') ? 'selected' : '' ?>><?= e($room['name'] ?? '') ?><?= !empty($room['position_code']) ? ' (' . e($room['position_code']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Status</label>
            <select name="status"><?php foreach (['pending','approved','rejected','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($ev['status'] ?? 'pending') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?></select>
        </div>
    </div>
    <button type="submit">Save</button>
</form>
</div>
<?php page_end(); ?>
