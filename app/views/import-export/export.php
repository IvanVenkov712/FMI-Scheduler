<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$entities = isset($entities) && is_array($entities) ? $entities : [];

ui_page_start('Export Data', [
    'active' => 'import-export',
    'subtitle' => 'Generate on-demand exports for schedules, rooms, routes, requests, and other entities.',
]);
?>

<section class="panel">
    <form method="get" class="stack">
        <div class="fields">
            <div class="field">
                <label for="entity">Entity</label>
                <select id="entity" name="entity">
                    <?php foreach ($entities as $entity): ?>
                        <option value="<?= ui_escape((string) $entity) ?>"><?= ui_escape((string) $entity) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="format">Format</label>
                <select id="format" name="format">
                    <?php foreach (['csv', 'json', 'xml', 'txt', 'ascii'] as $format): ?>
                        <option value="<?= ui_escape($format) ?>"><?= ui_escape(strtoupper($format)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="entity_id">Entity ID</label>
                <input type="number" id="entity_id" name="entity_id">
            </div>
            <div class="field">
                <label for="download">Download</label>
                <select id="download" name="download">
                    <option value="1">Yes</option>
                    <option value="">No</option>
                </select>
            </div>
        </div>
        <button type="submit">Run Export</button>
    </form>
</section>

<?php ui_page_end(); ?>
