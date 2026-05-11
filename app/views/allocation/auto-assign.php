<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$scheduleId = isset($scheduleId) ? (string) $scheduleId : '';

ui_page_start('Auto Assign', [
    'active' => 'allocation',
    'subtitle' => 'Trigger automatic room allocation for all pending events or for a specific schedule.',
]);
?>

<section class="panel">
    <form method="post" class="stack" id="auto-assign-form">
        <div class="fields">
            <div class="field">
                <label for="schedule_id">Schedule ID</label>
                <input type="number" id="schedule_id" name="schedule_id" value="<?= ui_escape($scheduleId) ?>">
                <small>Leave empty to process all pending events.</small>
            </div>
        </div>
        <button type="submit">Run Auto Assignment</button>
    </form>
    <div id="auto-assign-result"></div>
</section>

<?php ui_page_end(['allocation.js']); ?>
