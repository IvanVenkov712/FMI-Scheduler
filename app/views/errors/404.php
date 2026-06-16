<?php require_once __DIR__ . '/../shared/ui.php'; page_start('404 Not Found'); ?>
<div class="card"><div class="alert err"><?= e($message ?? 'The page or record you are looking for was not found.') ?></div><a href="/dashboard" class="btn">Back to Dashboard</a></div>
<?php page_end(); ?>
