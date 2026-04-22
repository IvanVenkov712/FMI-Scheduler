<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';
$message = isset($message) ? (string) $message : 'You do not have permission to access this page.';
ui_page_start('403 Forbidden', ['subtitle' => 'The backend rejected the request because the current account lacks the needed permissions.']);
?>
<section class="panel"><div class="alert alert-danger"><?= ui_escape($message) ?></div></section>
<?php ui_page_end(); ?>
