<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$buildings = isset($buildings) && is_array($buildings) ? $buildings : [];
$floor = isset($floor) && is_array($floor) ? $floor : [];
$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
$points = isset($points) && is_array($points) ? $points : [];
$hasPlanContext = $rooms !== [] || $points !== [] || array_key_exists('ascii_plan', $floor);

if (!$hasPlanContext) {
    $pageTitle = 'Edit Floor';
    $pageSubtitle = 'Update metadata and stored plan data for this floor.';
    $activeNav = 'floors';
    $formAction = '/floors/' . (string) ($floor['id'] ?? '');
    $submitLabel = 'Save Floor';
    $formMethod = 'PUT';
    $entity = $floor;
    $fields = [
        ['name' => 'building_id', 'label' => 'Building', 'type' => 'select', 'placeholder' => 'Select building', 'options' => array_map(static fn (array $row): array => [
            'value' => (string) ($row['id'] ?? ''),
            'label' => (string) (($row['code'] ?? '') . ' - ' . ($row['name'] ?? '')),
        ], $buildings)],
        ['name' => 'number', 'label' => 'Floor Number', 'type' => 'number'],
        ['name' => 'name', 'label' => 'Floor Name'],
        ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number'],
        ['name' => 'width_units', 'label' => 'Width Units', 'type' => 'number'],
        ['name' => 'height_units', 'label' => 'Height Units', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'Select status', 'options' => [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
        ]],
        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
        ['name' => 'ascii_plan', 'label' => 'ASCII Plan', 'type' => 'textarea', 'full' => true, 'rows' => 10],
        ['name' => 'plan_data', 'label' => 'Plan Data JSON', 'type' => 'textarea', 'full' => true, 'rows' => 8],
    ];

    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'crud-form.php';
    return;
}

ui_page_start('Floor Plan', [
    'active' => 'floors',
    'subtitle' => 'Edit the stored floor plan and inspect linked rooms and floor points.',
]);
?>

<section class="panel">
    <?php ui_render_actions([
        ['href' => '/floors/' . ($floor['id'] ?? ''), 'label' => 'Details'],
        ['href' => '/floors/' . ($floor['id'] ?? '') . '/rooms', 'label' => 'Rooms', 'class' => 'button-muted'],
    ]); ?>
</section>

<section class="split">
    <article class="panel">
        <h2>Plan Editor</h2>
        <form method="post" action="/floors/<?= ui_escape((string) ($floor['id'] ?? '')) ?>/plan" class="stack">
            <div class="field">
                <label for="ascii_plan">ASCII Plan</label>
                <textarea id="ascii_plan" name="ascii_plan" rows="18"><?= ui_escape((string) (($old['ascii_plan'] ?? null) ?? ($floor['ascii_plan'] ?? ''))) ?></textarea>
            </div>
            <div class="field">
                <label for="plan_data">Plan Data JSON</label>
                <textarea id="plan_data" name="plan_data" rows="10"><?= ui_escape((string) (($old['plan_data'] ?? null) ?? ($floor['plan_data'] ?? ''))) ?></textarea>
            </div>
            <button type="submit">Save Plan</button>
        </form>
    </article>

    <article class="panel">
        <h2>Floor Metadata</h2>
        <dl class="meta">
            <div><dt>ID</dt><dd><?= ui_escape((string) ($floor['id'] ?? '-')) ?></dd></div>
            <div><dt>Name</dt><dd><?= ui_escape((string) ($floor['name'] ?? '-')) ?></dd></div>
            <div><dt>Building</dt><dd><?= ui_escape((string) ($floor['building_id'] ?? '-')) ?></dd></div>
            <div><dt>Number</dt><dd><?= ui_escape((string) ($floor['number'] ?? '-')) ?></dd></div>
            <div><dt>Capacity</dt><dd><?= ui_escape((string) ($floor['capacity'] ?? '-')) ?></dd></div>
            <div><dt>Status</dt><dd><?= ui_escape((string) ($floor['status'] ?? '-')) ?></dd></div>
        </dl>

        <h3 style="margin-top:18px;">Points</h3>
        <?php if ($points === []): ?>
            <div class="empty">No floor points are currently stored.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Code</th><th>Type</th><th>Label</th></tr></thead>
                    <tbody>
                        <?php foreach ($points as $point): ?>
                            <tr>
                                <td><?= ui_escape((string) ($point['code'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($point['type'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($point['label'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="panel">
    <h2>Rooms on This Floor</h2>
    <?php if ($rooms === []): ?>
        <div class="empty">No rooms are assigned to this floor yet.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Code</th><th>Name</th><th>Capacity</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><a href="/rooms/<?= ui_escape((string) ($room['id'] ?? '')) ?>"><?= ui_escape((string) ($room['code'] ?? '-')) ?></a></td>
                            <td><?= ui_escape((string) ($room['name'] ?? '-')) ?></td>
                            <td><?= ui_escape((string) ($room['capacity'] ?? '-')) ?></td>
                            <td><?= ui_escape((string) ($room['status'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php ui_page_end(); ?>
