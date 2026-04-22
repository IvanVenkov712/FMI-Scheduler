<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$request = isset($request) && is_array($request) ? $request : [];

ui_page_start((string) ($request['course_name'] ?? 'Request Details'), [
    'active' => 'requests',
    'subtitle' => 'Review request payload and proceed to review or event scheduling.',
]);
?>

<section class="panel">
    <?php ui_render_actions([
        ['href' => '/requests/' . ($request['id'] ?? '') . '/edit', 'label' => 'Edit Request'],
        ['href' => '/requests/' . ($request['id'] ?? '') . '/review', 'label' => 'Review', 'class' => 'button-muted'],
    ]); ?>
</section>

<section class="panel">
    <dl class="meta">
        <div><dt>Status</dt><dd><span class="<?= ui_escape(ui_status_class($request['status'] ?? '')) ?>"><?= ui_escape((string) ($request['status'] ?? '-')) ?></span></dd></div>
        <div><dt>Students</dt><dd><?= ui_escape((string) ($request['students_count'] ?? '-')) ?></dd></div>
        <div><dt>Preferred Room</dt><dd><?= ui_escape((string) ($request['preferred_room_id'] ?? '-')) ?></dd></div>
        <div><dt>Delivery Mode</dt><dd><?= ui_escape((string) ($request['preferred_delivery_mode'] ?? '-')) ?></dd></div>
        <div><dt>Starts</dt><dd><?= ui_escape(ui_format_datetime($request['preferred_starts_at'] ?? null)) ?></dd></div>
        <div><dt>Ends</dt><dd><?= ui_escape(ui_format_datetime($request['preferred_ends_at'] ?? null)) ?></dd></div>
        <div><dt>Online URL</dt><dd><?= ui_escape((string) ($request['online_url'] ?? '-')) ?></dd></div>
        <div><dt>Required Resources</dt><dd><?= ui_escape((string) ($request['required_resources'] ?? '-')) ?></dd></div>
        <div><dt>Notes</dt><dd><?= ui_escape((string) ($request['notes'] ?? '-')) ?></dd></div>
    </dl>
</section>
