<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';
$message = isset($message) ? (string) $message : 'The requested page or record could not be found.';
ui_page_start('404 Not Found', ['subtitle' => 'The URL resolved correctly through the app shell, but no matching page or entity was found.']);
?>
<section class="panel"><div class="alert alert-danger"><?= ui_escape($message) ?></div></section>
<?php ui_page_end(); ?>
