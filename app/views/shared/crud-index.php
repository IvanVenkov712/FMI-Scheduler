<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'ui.php';

$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Records';
$pageSubtitle = isset($pageSubtitle) ? (string) $pageSubtitle : '';
$activeNav = isset($activeNav) ? (string) $activeNav : 'dashboard';
$items = isset($items) && is_array($items) ? $items : [];
$columns = isset($columns) && is_array($columns) ? $columns : [];
$actions = isset($actions) && is_array($actions) ? $actions : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$filterFields = isset($filterFields) && is_array($filterFields) ? $filterFields : [];

ui_page_start($pageTitle, [
    'active' => $activeNav,
    'subtitle' => $pageSubtitle,
    'actions' => $actions,
]);
?>

<?php if ($filterFields !== []): ?>
    <section class="panel">
        <h2>Filters</h2>
        <form method="get" class="stack">
            <div class="fields">
                <?php foreach ($filterFields as $field): ?>
                    <?php
                    if (!is_array($field)) {
                        continue;
                    }

                    $name = (string) ($field['name'] ?? '');
                    $label = (string) ($field['label'] ?? $name);
                    $type = (string) ($field['type'] ?? 'text');
                    $value = $filters[$name] ?? '';
                    ?>
                    <div class="field">
                        <label for="<?= ui_escape($name) ?>"><?= ui_escape($label) ?></label>
                        <?php if ($type === 'select'): ?>
                            <select id="<?= ui_escape($name) ?>" name="<?= ui_escape($name) ?>">
                                <option value="">All</option>
                                <?php foreach ((array) ($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                                    <option value="<?= ui_escape((string) $optionValue) ?>" <?= (string) $value === (string) $optionValue ? 'selected' : '' ?>>
                                        <?= ui_escape((string) $optionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="<?= ui_escape($type) ?>" id="<?= ui_escape($name) ?>" name="<?= ui_escape($name) ?>" value="<?= ui_escape((string) $value) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions-bar">
                <button type="submit">Apply Filters</button>
                <a class="button button-link button-muted" href="<?= ui_escape((string) ($_SERVER['PHP_SELF'] ?? '')) ?>">Reset</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Results</h2>
    <?php if ($items === []): ?>
        <div class="empty">No records found for the current query.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <th><?= ui_escape((string) ($column['label'] ?? 'Column')) ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <?php
                                $value = '';
                                if (isset($column['render']) && is_callable($column['render'])) {
                                    $value = (string) $column['render']($item);
                                } elseif (isset($column['value'])) {
                                    $value = (string) ($item[$column['value']] ?? '');
                                }
                                ?>
                                <td><?= $value ?></td>
                            <?php endforeach; ?>
                            <td>
                                <?php
                                $rowActions = isset($columnActions) && is_callable($columnActions)
                                    ? (array) $columnActions($item)
                                    : [];
                                ui_render_actions($rowActions);
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php ui_page_end(isset($pageScripts) && is_array($pageScripts) ? $pageScripts : []); ?>
