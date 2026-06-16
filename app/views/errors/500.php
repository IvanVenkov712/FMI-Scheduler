<?php require_once __DIR__ . '/../shared/ui.php'; page_start('500 Server Error'); ?>
<div class="card">
    <div class="alert err"><?= e($message ?? 'Something went wrong while processing the request.') ?></div>
    <a href="/dashboard" class="btn">Back to Dashboard</a>
</div>
<?php page_end(); ?>
