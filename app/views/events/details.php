<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$event = isset($event) && is_array($event) ? $event : [];
$onlineSession = isset($online_session) && is_array($online_session) ? $online_session : [];

ui_page_start((string) ($event['title'] ?? 'Event Details'), [
    'active' => 'events',
    'subtitle' => 'Inspect timing, assigned room, delivery mode, and online session details.',
]);
?>

<section class="panel">
    <?php ui_render_actions([
        ['href' => '/events/' . ($event['id'] ?? '') . '/edit', 'label' => 'Edit Event'],
        ['href' => '/events/' . ($event['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this event?'],
    ]); ?>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Overview</h2>
        <dl class="meta">
            <div><dt>Type</dt><dd><?= ui_escape((string) ($event['type'] ?? '-')) ?></dd></div>
            <div><dt>Status</dt><dd><span class="<?= ui_escape(ui_status_class($event['status'] ?? '')) ?>"><?= ui_escape((string) ($event['status'] ?? '-')) ?></span></dd></div>
            <div><dt>Course</dt><dd><?= ui_escape((string) ($event['course_name'] ?? '-')) ?></dd></div>
            <div><dt>Group</dt><dd><?= ui_escape((string) ($event['group_name'] ?? '-')) ?></dd></div>
            <div><dt>Room ID</dt><dd><?= ui_escape((string) ($event['room_id'] ?? '-')) ?></dd></div>
            <div><dt>Students</dt><dd><?= ui_escape((string) ($event['students_count'] ?? '-')) ?></dd></div>
            <div><dt>Starts</dt><dd><?= ui_escape(ui_format_datetime($event['starts_at'] ?? null)) ?></dd></div>
            <div><dt>Ends</dt><dd><?= ui_escape(ui_format_datetime($event['ends_at'] ?? null)) ?></dd></div>
            <div><dt>Mode</dt><dd><?= ui_escape((string) ($event['delivery_mode'] ?? '-')) ?></dd></div>
            <div><dt>Description</dt><dd><?= ui_escape((string) ($event['description'] ?? '-')) ?></dd></div>
        </dl>
    </article>

    <article class="panel">
        <h2>Online Session</h2>
        <?php if ($onlineSession === []): ?>
            <div class="empty">No online session metadata is linked to this event.</div>
        <?php else: ?>
            <dl class="meta">
                <div><dt>Platform</dt><dd><?= ui_escape((string) ($onlineSession['platform'] ?? '-')) ?></dd></div>
                <div><dt>URL</dt><dd><?= ui_escape((string) ($onlineSession['url'] ?? '-')) ?></dd></div>
                <div><dt>Access Key</dt><dd><?= ui_escape((string) ($onlineSession['access_key'] ?? '-')) ?></dd></div>
                <div><dt>Moderator</dt><dd><?= ui_escape((string) ($onlineSession['moderator_name'] ?? '-')) ?></dd></div>
                <div><dt>Instructions</dt><dd><?= ui_escape((string) ($onlineSession['access_instructions'] ?? '-')) ?></dd></div>
            </dl>
        <?php endif; ?>
    </article>
</section>
