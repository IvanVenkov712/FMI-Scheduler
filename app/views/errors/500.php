<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';
$message = isset($message) ? (string) $message : 'The application hit an unexpected error while rendering the page.';
ui_page_start('500 Server Error', ['subtitle' => 'The frontend shell loaded, but the backend failed while handling the request.']);
?>
<section class="panel"><div class="alert alert-danger"><?= ui_escape($message) ?></div></section>
<?php ui_page_end(); ?>
