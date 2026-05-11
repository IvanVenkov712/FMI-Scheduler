<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$entities = isset($entities) && is_array($entities) ? $entities : [];
$error = isset($error) ? (string) $error : '';
$result = isset($result) && is_array($result) ? $result : [];

ui_page_start('Import Data', [
    'active' => 'import-export',
    'subtitle' => 'Upload CSV, JSON, XML, TXT, or ASCII payloads for the supported backend entities.',
]);
?>

<?php ui_alert($error, 'danger'); ?>

<section class="panel">
    <form method="post" enctype="multipart/form-data" class="stack">
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
                <label for="floor_id">Floor ID (for floor ASCII import)</label>
                <input type="number" id="floor_id" name="floor_id">
            </div>
            <div class="field">
                <label for="file">File</label>
                <input type="file" id="file" name="file" required>
            </div>
        </div>
        <button type="submit">Start Import</button>
    </form>
</section>

<?php if ($result !== []): ?>
    <section class="panel">
        <h2>Last Result</h2>
        <pre class="code"><?= ui_escape((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
    </section>
<?php endif; ?>

<?php ui_page_end(['import-export.js']); ?>