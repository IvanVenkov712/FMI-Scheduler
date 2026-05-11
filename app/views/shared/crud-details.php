<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'ui.php';

$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Details';
$pageSubtitle = isset($pageSubtitle) ? (string) $pageSubtitle : '';
$activeNav = isset($activeNav) ? (string) $activeNav : 'dashboard';
$summary = isset($summary) && is_array($summary) ? $summary : [];
$sections = isset($sections) && is_array($sections) ? $sections : [];
$actions = isset($actions) && is_array($actions) ? $actions : [];

ui_page_start($pageTitle, [
    'active' => $activeNav,
    'subtitle' => $pageSubtitle,
]);
?>

<?php if ($actions !== []): ?>
    <section class="panel">
        <?php ui_render_actions($actions); ?>
    </section>
<?php endif; ?>

<?php if ($summary !== []): ?>
    <section class="panel">
        <h2>Overview</h2>
        <dl class="meta">
            <?php foreach ($summary as $label => $value): ?>
                <div>
                    <dt><?= ui_escape((string) $label) ?></dt>
                    <dd><?= is_string($value) ? $value : ui_escape((string) $value) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </section>
<?php endif; ?>

<?php foreach ($sections as $section): ?>
    <?php if (!is_array($section)) {
        continue;
    } ?>
    <section class="panel">
        <h2><?= ui_escape((string) ($section['title'] ?? 'Section')) ?></h2>
        <?php if (!empty($section['copy'])): ?>
            <p class="panel-copy"><?= ui_escape((string) $section['copy']) ?></p>
        <?php endif; ?>
        <?php if (!empty($section['html'])): ?>
            <?= (string) $section['html'] ?>
        <?php elseif (!empty($section['rows']) && is_array($section['rows'])): ?>
            <dl class="meta">
                <?php foreach ($section['rows'] as $label => $value): ?>
                    <div>
                        <dt><?= ui_escape((string) $label) ?></dt>
                        <dd><?= is_string($value) ? $value : ui_escape((string) $value) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php else: ?>
            <div class="empty">No additional data.</div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php ui_page_end(isset($pageScripts) && is_array($pageScripts) ? $pageScripts : []); ?>
