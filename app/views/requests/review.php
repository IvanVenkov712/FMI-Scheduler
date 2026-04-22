<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$request = isset($request) && is_array($request) ? $request : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];

ui_page_start('Review Request', [
    'active' => 'requests',
    'subtitle' => 'Approve, reject, or cancel a request and optionally turn it into an event.',
]);
?>

<section class="grid two">
    <article class="panel">
        <h2>Request Summary</h2>
        <dl class="meta">
            <div><dt>Course</dt><dd><?= ui_escape((string) ($request['course_name'] ?? '-')) ?></dd></div>
            <div><dt>Status</dt><dd><?= ui_escape((string) ($request['status'] ?? '-')) ?></dd></div>
            <div><dt>Students</dt><dd><?= ui_escape((string) ($request['students_count'] ?? '-')) ?></dd></div>
            <div><dt>Preferred Mode</dt><dd><?= ui_escape((string) ($request['preferred_delivery_mode'] ?? '-')) ?></dd></div>
            <div><dt>Preferred Room</dt><dd><?= ui_escape((string) ($request['preferred_room_id'] ?? '-')) ?></dd></div>
            <div><dt>Preferred Start</dt><dd><?= ui_escape(ui_format_datetime($request['preferred_starts_at'] ?? null)) ?></dd></div>
        </dl>
    </article>

    <article class="panel">
        <h2>Review Action</h2>
        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">Review submission failed. Please check the chosen status.</div>
        <?php endif; ?>
        <form method="post" action="/requests/<?= ui_escape((string) ($request['id'] ?? '')) ?>/review" class="stack">
            <div class="field">
                <label for="status">Decision</label>
                <select id="status" name="status">
                    <?php foreach (['approved', 'rejected', 'cancelled'] as $status): ?>
                        <option value="<?= ui_escape($status) ?>" <?= (string) ($old['status'] ?? '') === $status ? 'selected' : '' ?>><?= ui_escape(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="5"><?= ui_escape((string) ($old['notes'] ?? $request['notes'] ?? '')) ?></textarea>
            </div>
            <button type="submit">Save Review</button>
        </form>

        <form method="post" action="/requests/<?= ui_escape((string) ($request['id'] ?? '')) ?>/schedule" class="stack" style="margin-top:18px;">
            <button type="submit">Schedule Request as Event</button>
        </form>
    </article>
</section>

<?php ui_page_end(); ?>
