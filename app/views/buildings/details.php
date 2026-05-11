<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$building = isset($building) && is_array($building) ? $building : [];
$orientation = isset($orientation) ? (string) $orientation : (string) ($building['orientation'] ?? 'east');
$verticalView = isset($vertical_view) ? (string) $vertical_view : (string) ($building['vertical_view_' . $orientation] ?? $building['schematic_description'] ?? '');
$title = (string) ($building['name'] ?? 'Building Details');

ui_page_start($title, [
    'active' => 'buildings',
    'subtitle' => 'Inspect building metadata, switch between vertical views, and drill down to floors.',
]);
?>

<section class="panel">
    <?php ui_render_actions([
        ['href' => '/buildings/' . ($building['id'] ?? '') . '/edit', 'label' => 'Edit Building'],
        ['href' => '/buildings/' . ($building['id'] ?? '') . '/floors', 'label' => 'Open Floors', 'class' => 'button-muted'],
        ['href' => '/buildings/' . ($building['id'] ?? ''), 'label' => 'Delete', 'method' => 'DELETE', 'class' => 'button-danger', 'confirm' => 'Delete this building?'],
    ]); ?>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Overview</h2>
        <dl class="meta">
            <div><dt>Code</dt><dd><?= ui_escape((string) ($building['code'] ?? '-')) ?></dd></div>
            <div><dt>Status</dt><dd><span class="<?= ui_escape(ui_status_class($building['status'] ?? '')) ?>"><?= ui_escape((string) ($building['status'] ?? '-')) ?></span></dd></div>
            <div><dt>Address</dt><dd><?= ui_escape((string) ($building['address'] ?? '-')) ?></dd></div>
            <div><dt>Floors Count</dt><dd><?= ui_escape((string) ($building['floors_count'] ?? '-')) ?></dd></div>
            <div><dt>Orientation</dt><dd><?= ui_escape((string) ($building['orientation'] ?? '-')) ?></dd></div>
            <div><dt>Description</dt><dd><?= ui_escape((string) ($building['description'] ?? '-')) ?></dd></div>
        </dl>
    </article>

    <article class="panel">
        <h2>Vertical View</h2>
        <div class="pill-list">
            <?php foreach (['east', 'west', 'north', 'south'] as $view): ?>
<<<<<<< HEAD
                <a class="pill" href="/buildings/<?= ui_escape((string) ($building['id'] ?? '')) ?>/vertical-view/<?= ui_escape($view) ?>"><?= ui_escape(ucfirst($view)) ?></a>
            <?php endforeach; ?>
        </div>
        <p class="panel-copy">Current orientation: <?= ui_escape($orientation) ?></p>
        <?php if ($verticalView !== ''): ?>
            <pre class="code"><?= ui_escape($verticalView) ?></pre>
=======
                <a class="pill <?= $orientation === $view ? 'active' : '' ?>" href="/buildings/<?= ui_escape((string) ($building['id'] ?? '')) ?>/vertical-view/<?= ui_escape($view) ?>"><?= ui_escape(ucfirst($view)) ?></a>
            <?php endforeach; ?>
        </div>
        <p class="panel-copy" style="margin-top:12px;">Current orientation: <strong><?= ui_escape($orientation) ?></strong></p>
        <?php if ($verticalView !== ''): ?>
            <pre class="ascii-preview"><?= ui_escape($verticalView) ?></pre>
>>>>>>> 7329beb (Added frontend)
        <?php else: ?>
            <div class="empty">No vertical view content stored for this orientation.</div>
        <?php endif; ?>
    </article>
</section>

<<<<<<< HEAD
<?php ui_page_end(); ?>
=======
<?php if (!empty($building['schematic_description']) && $verticalView !== ($building['schematic_description'] ?? '')): ?>
<section class="panel">
    <h2>Schematic Description</h2>
    <pre class="ascii-preview"><?= ui_escape((string) $building['schematic_description']) ?></pre>
</section>
<?php endif; ?>

<?php ui_page_end(['building-editor.js']); ?>
>>>>>>> 7329beb (Added frontend)
