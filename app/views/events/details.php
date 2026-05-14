<?php
require_once __DIR__ . '/../shared/ui.php';
$ev = is_object($event ?? null) ? $event->to_array() : ($event ?? []);
$rm = is_object($room ?? null) ? $room->to_array() : ($room ?? []);
$oa = is_object($online_access ?? null) ? $online_access->to_array() : ($online_access ?? []);
$reqs = $resource_requirements ?? [];
page_start($ev['name'] ?? 'Event Details', 'events');
?>
<div class="actions page-actions">
    <a href="/events/<?= e($ev['id']) ?>/edit" class="btn">Edit</a>
    <?php if (($ev['status'] ?? '') === 'pending'): ?>
    <form method="post" action="/events/<?= e($ev['id']) ?>/approve" class="inline-form"><button class="btn btn-success">Approve</button></form>
    <form method="post" action="/events/<?= e($ev['id']) ?>/reject" class="inline-form"><button class="btn btn-danger">Reject</button></form>
    <?php endif; ?>
    <a href="/events" class="btn btn-muted">Back</a>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Event Info</h2>
        <dl class="meta">
            <div><dt>Name</dt><dd><?= e($ev['name'] ?? '-') ?></dd></div>
            <div><dt>Subject</dt><dd><?= e($ev['subject_name'] ?? '-') ?></dd></div>
            <div><dt>Type</dt><dd><?= e($ev['type'] ?? '-') ?></dd></div>
            <div><dt>Status</dt><dd><?= status_badge($ev['status'] ?? '') ?></dd></div>
            <div><dt>Students</dt><dd><?= e($ev['student_count'] ?? '-') ?></dd></div>
            <div><dt>Mode</dt><dd><?= e($ev['delivery_mode'] ?? '-') ?></dd></div>
            <div><dt>Start</dt><dd><?= fmt_dt($ev['start_time'] ?? null) ?></dd></div>
            <div><dt>End</dt><dd><?= fmt_dt($ev['end_time'] ?? null) ?></dd></div>
        </dl>
    </div>
    <div class="card">
        <h2>Room & Online Access</h2>
        <?php if ($rm): ?>
        <dl class="meta">
            <div><dt>Room</dt><dd><?= e($rm['name'] ?? '-') ?></dd></div>
            <div><dt>Capacity</dt><dd><?= e($rm['capacity'] ?? '-') ?></dd></div>
        </dl>
        <?php endif; ?>
        <?php if ($oa): ?>
        <dl class="meta meta-spaced">
            <div><dt>Platform</dt><dd><?= e($oa['platform'] ?? '-') ?></dd></div>
            <div><dt>Link</dt><dd><?= e($oa['meeting_link'] ?? $oa['link'] ?? '-') ?></dd></div>
        </dl>
        <?php endif; ?>
        <?php if ($reqs): ?>
        <h3 class="section-subtitle">Resources</h3>
        <ul><?php foreach ($reqs as $rq): $rq = is_object($rq) ? $rq->to_array() : $rq; ?>
            <li><?= e($rq['resource_id'] ?? $rq['name'] ?? '?') ?> &times; <?= e($rq['quantity'] ?? 1) ?></li>
        <?php endforeach; ?></ul>
        <?php endif; ?>
    </div>
</div>
<?php page_end(); ?>
