<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$floor = isset($floor) && is_array($floor) ? $floor : [];
$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];

ui_page_start((string) ($floor['name'] ?? 'Floor Details'), [
    'active' => 'floors',
    'subtitle' => 'Inspect floor metadata, plan visualisation, and linked rooms.',
    'actions' => [
        ['href' => '/floors/' . ($floor['id'] ?? '') . '/edit', 'label' => 'Edit Floor'],
        ['href' => '/floors/' . ($floor['id'] ?? '') . '/plan', 'label' => 'Edit Plan', 'class' => 'button-muted'],
        ['href' => '/floors/' . ($floor['id'] ?? '') . '/rooms', 'label' => 'Open Rooms', 'class' => 'button-muted'],
    ],
]);
?>

<section class="grid two">
    <article class="panel">
        <h2>Overview</h2>
        <dl class="meta">
            <div><dt>ID</dt><dd><?= ui_escape((string) ($floor['id'] ?? '-')) ?></dd></div>
            <div><dt>Building ID</dt><dd><?= ui_escape((string) ($floor['building_id'] ?? '-')) ?></dd></div>
            <div><dt>Number</dt><dd><?= ui_escape((string) ($floor['number'] ?? '-')) ?></dd></div>
            <div><dt>Capacity</dt><dd><?= ui_escape((string) ($floor['capacity'] ?? '-')) ?></dd></div>
            <div><dt>Width Units</dt><dd><?= ui_escape((string) ($floor['width_units'] ?? '-')) ?></dd></div>
            <div><dt>Height Units</dt><dd><?= ui_escape((string) ($floor['height_units'] ?? '-')) ?></dd></div>
            <div><dt>Status</dt><dd><span class="<?= ui_escape(ui_status_class($floor['status'] ?? '')) ?>"><?= ui_escape((string) ($floor['status'] ?? '-')) ?></span></dd></div>
            <div><dt>Description</dt><dd><?= ui_escape((string) ($floor['description'] ?? '-')) ?></dd></div>
        </dl>
    </article>

    <article class="panel">
        <h2>Floor Plan</h2>
        <?php
        $asciiPlan = (string) ($floor['ascii_plan'] ?? '');
        if ($asciiPlan !== ''):
        ?>
            <div class="plan-container" data-floor-id="<?= ui_escape((string) ($floor['id'] ?? '')) ?>">
                <div class="plan-canvas-wrap" style="position:relative;">
                    <canvas class="plan-canvas" data-ascii-plan="<?= ui_escape($asciiPlan) ?>"></canvas>
                </div>
            </div>
        <?php else: ?>
            <div class="empty">No ASCII plan stored for this floor. <a href="/floors/<?= ui_escape((string) ($floor['id'] ?? '')) ?>/plan">Create one</a>.</div>
        <?php endif; ?>
    </article>
</section>

<?php if ($rooms !== []): ?>
<section class="panel">
    <h2>Rooms on this Floor</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Code</th><th>Name</th><th>Capacity</th><th>Type</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?= ui_escape((string) ($room['code'] ?? '')) ?></td>
                        <td><a href="/rooms/<?= ui_escape((string) ($room['id'] ?? '')) ?>"><?= ui_escape((string) ($room['name'] ?? '')) ?></a></td>
                        <td><?= ui_escape((string) ($room['capacity'] ?? '-')) ?></td>
                        <td><?= ui_escape((string) ($room['delivery_mode'] ?? $room['room_type'] ?? '-')) ?></td>
                        <td><span class="<?= ui_escape(ui_status_class($room['status'] ?? '')) ?>"><?= ui_escape((string) ($room['status'] ?? '')) ?></span></td>
                        <td><a class="button button-link button-muted" href="/rooms/<?= ui_escape((string) ($room['id'] ?? '')) ?>">Details</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php ui_page_end(['building-editor.js', 'room-visualization.js']); ?>
