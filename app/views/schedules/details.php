<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$schedule = isset($schedule) && is_array($schedule) ? $schedule : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];

ui_page_start((string) ($schedule['name'] ?? 'Schedule Details'), [
    'active' => 'schedules',
    'subtitle' => 'Inspect schedule metadata and attach existing events to it.',
]);
?>

<section class="grid two">
    <article class="panel">
        <h2>Overview</h2>
        <dl class="meta">
            <div><dt>Type</dt><dd><?= ui_escape((string) ($schedule['type'] ?? '-')) ?></dd></div>
            <div><dt>Academic Year</dt><dd><?= ui_escape((string) ($schedule['academic_year'] ?? '-')) ?></dd></div>
            <div><dt>Semester</dt><dd><?= ui_escape((string) ($schedule['semester'] ?? '-')) ?></dd></div>
            <div><dt>Starts</dt><dd><?= ui_escape(ui_format_date($schedule['starts_on'] ?? null)) ?></dd></div>
            <div><dt>Ends</dt><dd><?= ui_escape(ui_format_date($schedule['ends_on'] ?? null)) ?></dd></div>
            <div><dt>Status</dt><dd><?= ui_escape((string) ($schedule['status'] ?? '-')) ?></dd></div>
        </dl>
        <div class="actions-bar" style="margin-top:16px;">
            <a class="button button-link" href="/schedules/<?= ui_escape((string) ($schedule['id'] ?? '')) ?>/edit">Edit</a>
            <a class="button button-link button-muted" href="/schedules/<?= ui_escape((string) ($schedule['id'] ?? '')) ?>/calendar">Calendar</a>
            <a class="button button-link button-muted" href="/allocation/auto-assign/<?= ui_escape((string) ($schedule['id'] ?? '')) ?>">Auto Assign</a>
        </div>
    </article>

    <article class="panel">
        <h2>Attach Event</h2>
        <?php if ($schedule === []): ?>
            <div class="empty">Schedule data is required to attach events.</div>
        <?php else: ?>
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger">Unable to attach event. Please verify the payload.</div>
            <?php endif; ?>
            <form method="post" action="/schedules/<?= ui_escape((string) ($schedule['id'] ?? '')) ?>/events" class="stack">
                <div class="fields">
                    <div class="field">
                        <label for="event_id">Event ID</label>
                        <input type="number" id="event_id" name="event_id" value="<?= ui_escape((string) ($old['event_id'] ?? '')) ?>">
                    </div>
                    <div class="field">
                        <label for="assigned_room_id">Assigned Room ID</label>
                        <input type="number" id="assigned_room_id" name="assigned_room_id" value="<?= ui_escape((string) ($old['assigned_room_id'] ?? '')) ?>">
                    </div>
                    <div class="field">
                        <label for="allocation_status">Allocation Status</label>
                        <input type="text" id="allocation_status" name="allocation_status" value="<?= ui_escape((string) ($old['allocation_status'] ?? 'manual')) ?>">
                    </div>
                    <div class="field full">
                        <label for="conflict_notes">Conflict Notes</label>
                        <textarea id="conflict_notes" name="conflict_notes" rows="4"><?= ui_escape((string) ($old['conflict_notes'] ?? '')) ?></textarea>
                    </div>
                </div>
                <button type="submit">Attach Event</button>
            </form>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(); ?>
