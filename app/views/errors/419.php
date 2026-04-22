<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';
$message = isset($message) ? (string) $message : 'The form session expired or the CSRF token is invalid.';
ui_page_start('419 Session Error', ['subtitle' => 'This usually happens when a stale form is submitted after the session token changed.']);
?>
<section class="panel"><div class="alert alert-danger"><?= ui_escape($message) ?></div></section>
<?php ui_page_end(); ?>
