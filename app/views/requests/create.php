<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Submit Request', 'requests'); ?>
<div class="card">
<form method="post" action="/requests" class="stack">
    <div class="fields">
        <div class="field"><label>Name</label><input type="text" name="name" maxlength="150" required></div>
        <div class="field"><label>Subject</label><input type="text" name="subject_name"></div>
        <div class="field"><label>Type</label>
            <select name="type"><option value="exam">Exam</option><option value="lecture">Lecture</option><option value="practice">Practice</option><option value="consultation">Consultation</option></select>
        </div>
        <div class="field"><label>Students</label><input type="number" name="student_count" value="30" min="0" step="1" required></div>
        <div class="field"><label>Start Time</label><input type="datetime-local" name="start_time" required></div>
        <div class="field"><label>End Time</label><input type="datetime-local" name="end_time" required></div>
        <div class="field"><label>Delivery Mode</label>
            <select name="delivery_mode"><option value="physical">Physical</option><option value="online">Online</option><option value="hybrid">Hybrid</option></select>
        </div>
        <div class="field"><label>Preferred Room</label>
            <select name="room_id">
                <option value="">No preference</option>
                <?php foreach ($rooms ?? [] as $room): $room = is_object($room) ? $room->to_array() : $room; ?>
                <option value="<?= e($room['id'] ?? '') ?>"><?= e($room['name'] ?? '') ?><?= !empty($room['position_code']) ? ' (' . e($room['position_code']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Online Platform</label><input type="text" name="online_access[platform]" placeholder="Zoom, Teams, Meet"></div>
        <div class="field"><label>Meeting Link</label><input type="url" name="online_access[meeting_link]"></div>
        <div class="field"><label>Access Key</label><input type="text" name="online_access[access_key]"></div>
        <div class="field"><label>Host Notes</label><input type="text" name="online_access[host_notes]"></div>
    </div>
    <button type="submit">Submit Request</button>
</form>
</div>
<?php page_end(); ?>
