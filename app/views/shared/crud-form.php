<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'ui.php';

$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Edit';
$pageSubtitle = isset($pageSubtitle) ? (string) $pageSubtitle : '';
$activeNav = isset($activeNav) ? (string) $activeNav : 'dashboard';
$formAction = isset($formAction) ? (string) $formAction : '#';
$formMethod = strtoupper((string) ($formMethod ?? 'POST'));
$submitLabel = isset($submitLabel) ? (string) $submitLabel : 'Save';
$actions = isset($actions) && is_array($actions) ? $actions : [];
$fields = isset($fields) && is_array($fields) ? $fields : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];
$entity = isset($entity) && is_array($entity) ? $entity : [];
$old = isset($old) && is_array($old) ? $old : [];

$valueFor = static function (string $name, mixed $default = '') use ($old, $entity): mixed {
    if (array_key_exists($name, $old)) {
        return $old[$name];
    }

    if (array_key_exists($name, $entity)) {
        return $entity[$name];
    }

    return $default;
};

$normalizeInputValue = static function (string $type, mixed $value): string {
    if ($value === null) {
        return '';
    }

    $value = (string) $value;

    if ($type === 'date') {
        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (Throwable) {
            return $value;
        }
    }

    if ($type === 'datetime-local') {
        try {
            return (new DateTimeImmutable($value))->format('Y-m-d\TH:i');
        } catch (Throwable) {
            return str_replace(' ', 'T', substr($value, 0, 16));
        }
    }

    return $value;
};

ui_page_start($pageTitle, [
    'active' => $activeNav,
    'subtitle' => $pageSubtitle,
    'actions' => $actions,
]);
?>

<section class="panel">
    <h2><?= ui_escape($submitLabel) ?></h2>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger">Please review the highlighted fields and try again.</div>
    <?php endif; ?>

    <form method="post" action="<?= ui_escape($formAction) ?>" class="stack" enctype="multipart/form-data">
        <?php if ($formMethod !== 'POST'): ?>
            <input type="hidden" name="_method" value="<?= ui_escape($formMethod) ?>">
        <?php endif; ?>

        <div class="fields">
            <?php foreach ($fields as $field): ?>
                <?php
                if (!is_array($field)) {
                    continue;
                }

                $name = (string) ($field['name'] ?? '');
                $label = (string) ($field['label'] ?? $name);
                $type = (string) ($field['type'] ?? 'text');
                $value = $valueFor($name, $field['default'] ?? '');
                $inputValue = $normalizeInputValue($type, $value);
                $isFull = !empty($field['full']);
                $fieldErrors = (array) ($errors[$name] ?? []);
                ?>
                <div class="field <?= $isFull ? 'full' : '' ?>">
                    <?php if ($type === 'checkbox'): ?>
                        <label class="checkbox" for="<?= ui_escape($name) ?>">
                            <input type="hidden" name="<?= ui_escape($name) ?>" value="0">
                            <input
                                type="checkbox"
                                id="<?= ui_escape($name) ?>"
                                name="<?= ui_escape($name) ?>"
                                value="1"
                                <?= in_array($value, [1, '1', true, 'true', 'on', 'yes'], true) ? 'checked' : '' ?>
                            >
                            <span><?= ui_escape($label) ?></span>
                        </label>
                    <?php else: ?>
                        <label for="<?= ui_escape($name) ?>"><?= ui_escape($label) ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea
                                id="<?= ui_escape($name) ?>"
                                name="<?= ui_escape($name) ?>"
                                rows="<?= ui_escape((string) ($field['rows'] ?? 5)) ?>"
                            ><?= ui_escape((string) $value) ?></textarea>
                        <?php elseif ($type === 'select'): ?>
                            <select id="<?= ui_escape($name) ?>" name="<?= ui_escape($name) ?>">
                                <?php if (!empty($field['placeholder'])): ?>
                                    <option value=""><?= ui_escape((string) $field['placeholder']) ?></option>
                                <?php endif; ?>
                                <?php foreach ((array) ($field['options'] ?? []) as $option): ?>
                                    <?php
                                    if (is_array($option)) {
                                        $optionValue = (string) ($option['value'] ?? '');
                                        $optionLabel = (string) ($option['label'] ?? $optionValue);
                                    } else {
                                        $optionValue = (string) $option;
                                        $optionLabel = (string) $option;
                                    }
                                    ?>
                                    <option value="<?= ui_escape($optionValue) ?>" <?= (string) $value === $optionValue ? 'selected' : '' ?>>
                                        <?= ui_escape($optionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input
                                type="<?= ui_escape($type) ?>"
                                id="<?= ui_escape($name) ?>"
                                name="<?= ui_escape($name) ?>"
                                value="<?= $type === 'file' ? '' : ui_escape($inputValue) ?>"
                                <?= !empty($field['step']) ? 'step="' . ui_escape((string) $field['step']) . '"' : '' ?>
                            >
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($field['hint'])): ?>
                        <small><?= ui_escape((string) $field['hint']) ?></small>
                    <?php endif; ?>

                    <?php foreach ($fieldErrors as $fieldError): ?>
                        <div class="field-error"><?= ui_escape((string) $fieldError) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions-bar">
            <button type="submit"><?= ui_escape($submitLabel) ?></button>
        </div>
    </form>
</section>

<?php ui_page_end(isset($pageScripts) && is_array($pageScripts) ? $pageScripts : []); ?>
