<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Create Event', 'events'); ?>
<div class="card">
<form method="post" action="/events/submit-request" class="stack">
    <div class="fields">
        <div class="field"><label>Name</label><input type="text" name="name" required></div>
        <div class="field"><label>Subject</label><input type="text" name="subject_name"></div>
        <div class="field"><label>Type</label>
            <select name="type"><option value="lecture">Lecture</option><option value="exam">Exam</option><option value="practice">Practice</option><option value="consultation">Consultation</option></select>
        </div>
        <div class="field"><label>Students</label><input type="number" name="student_count" value="30"></div>
        <div class="field"><label>Start Time</label><input type="datetime-local" name="start_time" required></div>
        <div class="field"><label>End Time</label><input type="datetime-local" name="end_time" required></div>
        <div class="field"><label>Delivery Mode</label>
            <select name="delivery_mode"><option value="physical">Physical</option><option value="online">Online</option><option value="hybrid">Hybrid</option></select>
        </div>
        <div class="field"><label>Preferred Room ID</label><input type="number" name="room_id"></div>
    </div>
    <button type="submit">Submit Event Request</button>
</form>
</div>
<?php page_end(); ?>
