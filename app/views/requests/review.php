<?php
require_once __DIR__ . '/../shared/ui.php';
$ev = is_object($event ?? null) ? $event->to_array() : ($event ?? []);
$reqs = $resource_requirements ?? [];
$oa = is_object($online_access ?? null) ? $online_access->to_array() : ($online_access ?? []);
page_start('Review Request', 'requests');
?>
<div class="grid-2">
    <div class="card">
        <h2>Request Details</h2>
        <dl class="meta">
            <div><dt>Name</dt><dd><?= e($ev['name'] ?? '-') ?></dd></div>
            <div><dt>Subject</dt><dd><?= e($ev['subject_name'] ?? '-') ?></dd></div>
            <div><dt>Type</dt><dd><?= e($ev['type'] ?? '-') ?></dd></div>
            <div><dt>Students</dt><dd><?= e($ev['student_count'] ?? '-') ?></dd></div>
            <div><dt>Mode</dt><dd><?= e($ev['delivery_mode'] ?? '-') ?></dd></div>
            <div><dt>Start</dt><dd><?= fmt_dt($ev['start_time'] ?? null) ?></dd></div>
            <div><dt>End</dt><dd><?= fmt_dt($ev['end_time'] ?? null) ?></dd></div>
            <div><dt>Status</dt><dd><?= status_badge($ev['status'] ?? '') ?></dd></div>
        </dl>
        <?php if ($reqs): ?><h3 class="section-subtitle">Resources</h3>
        <ul><?php foreach ($reqs as $rq): $rq = is_object($rq) ? $rq->to_array() : $rq; ?>
            <li><?= e($rq['resource_name'] ?? $rq['name'] ?? $rq['resource_id'] ?? '?') ?> &times; <?= e($rq['required_quantity'] ?? $rq['quantity'] ?? 1) ?></li>
        <?php endforeach; ?></ul><?php endif; ?>
    </div>
    <div class="card">
        <h2>Actions</h2>
        <?php if (($ev['status'] ?? '') === 'pending'): ?>
        <div class="actions action-stack">
            <form method="post" action="/events/<?= e($ev['id']) ?>/approve"><button class="btn btn-success btn-block">Approve</button></form>
            <form method="post" action="/events/<?= e($ev['id']) ?>/reject"><button class="btn btn-danger btn-block">Reject</button></form>
        </div>
        <?php else: ?>
        <div class="empty">This request has already been <?= e($ev['status'] ?? 'processed') ?>.</div>
        <?php endif; ?>
    </div>
</div>
<?php page_end(); ?>
