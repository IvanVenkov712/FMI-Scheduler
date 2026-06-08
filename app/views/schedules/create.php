<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Create Schedule', 'schedules'); ?>
<div class="card">
<form method="post" action="/schedules" class="stack">
        <div class="fields">
            <div class="field"><label>Name</label><input type="text" name="name" maxlength="150" required></div>
            <div class="field"><label>Start Date</label><input type="date" name="start_date" required></div>
            <div class="field"><label>End Date</label><input type="date" name="end_date" required></div>
            <div class="field full"><label>Description</label><textarea name="description" rows="4"></textarea></div>
    </div>
    <button type="submit">Create Schedule</button>
</form>
</div>
<?php page_end(); ?>
