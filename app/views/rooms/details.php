<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$room = isset($room) && is_array($room) ? $room : [];

ui_page_start((string) ($room['name'] ?? 'Room Details'), [
    'active' => 'rooms',
    'subtitle' => 'Inspect room metadata, capacity, coordinates, and linked availability checks.',
]);
?>

<section class="panel">
    <?php ui_render_actions([
        ['href' => '/rooms/' . ($room['id'] ?? '') . '/edit', 'label' => 'Edit Room'],
        ['href' => '/rooms/' . ($room['id'] ?? '') . '/availability', 'label' => 'Availability', 'class' => 'button-muted'],
        ['href' => '/resources/manage/' . ($room['id'] ?? ''), 'label' => 'Manage Resources', 'class' => 'button-muted'],
    ]); ?>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Overview</h2>
        <dl class="meta">
            <div><dt>Code</dt><dd><?= ui_escape((string) ($room['code'] ?? '-')) ?></dd></div>
            <div><dt>Status</dt><dd><span class="<?= ui_escape(ui_status_class($room['status'] ?? '')) ?>"><?= ui_escape((string) ($room['status'] ?? '-')) ?></span></dd></div>
            <div><dt>Floor ID</dt><dd><?= ui_escape((string) ($room['floor_id'] ?? '-')) ?></dd></div>
            <div><dt>Room Type ID</dt><dd><?= ui_escape((string) ($room['room_type_id'] ?? '-')) ?></dd></div>
            <div><dt>Capacity</dt><dd><?= ui_escape((string) ($room['capacity'] ?? '-')) ?></dd></div>
            <div><dt>Covid Capacity</dt><dd><?= ui_escape((string) ($room['covid_capacity'] ?? '-')) ?></dd></div>
            <div><dt>Description</dt><dd><?= ui_escape((string) ($room['description'] ?? '-')) ?></dd></div>
            <div><dt>Online URL</dt><dd><?= ui_escape((string) ($room['online_url'] ?? '-')) ?></dd></div>
        </dl>
    </article>

    <article class="panel">
        <h2>Geometry</h2>
        <dl class="meta">
            <div><dt>X</dt><dd><?= ui_escape((string) ($room['x'] ?? '-')) ?></dd></div>
            <div><dt>Y</dt><dd><?= ui_escape((string) ($room['y'] ?? '-')) ?></dd></div>
            <div><dt>Width</dt><dd><?= ui_escape((string) ($room['width'] ?? '-')) ?></dd></div>
            <div><dt>Height</dt><dd><?= ui_escape((string) ($room['height'] ?? '-')) ?></dd></div>
            <div><dt>Access Key</dt><dd><?= ui_escape((string) ($room['access_key'] ?? '-')) ?></dd></div>
        </dl>
    </article>
</section>

<?php ui_page_end(); ?>
