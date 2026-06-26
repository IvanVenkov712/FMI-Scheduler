<?php
require_once __DIR__ . '/../shared/ui.php';

$ev = is_object($event ?? null) ? $event->to_array() : ($event ?? []);
$requestor_data = is_object($requestor ?? null) ? $requestor->to_array() : ($requestor ?? []);
$rm = is_object($room ?? null) ? $room->to_array() : ($room ?? []);
$oa = is_object($online_access ?? null) ? $online_access->to_array() : ($online_access ?? []);
$reqs = $resource_requirements ?? [];
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Event Export</title>
<link rel="stylesheet" href="/css/style.css">
<style>
body { background: #f6f7fb; color: #172033; }
.static-export-page { max-width: 980px; margin: 32px auto; padding: 0 18px 32px; }
.static-export-header { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; margin-bottom: 18px; }
.static-export-header h1 { margin: 0 0 6px; }
.static-export-meta { color: #5d667a; font-size: 0.95rem; }
@media print {
    body { background: #fff; }
    .static-export-page { margin: 0; max-width: none; padding: 0; }
    .no-print { display: none; }
    .card { box-shadow: none; border: 1px solid #d8dde8; }
}
</style>
</head>
<body>
<main class="static-export-page">
    <div class="static-export-header">
        <div>
            <h1>Event Export</h1>
            <div class="static-export-meta">Generated at <?= e($generated_at ?? date('Y-m-d H:i:s')) ?></div>
        </div>
        <div class="actions no-print">
            <button type="button" onclick="window.print()">Print</button>
            <a href="/events/<?= e($ev['id'] ?? '') ?>" class="btn btn-muted">Back</a>
        </div>
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
            <h2>Requestor & Location</h2>
            <dl class="meta">
                <div><dt>Requestor</dt><dd><?= e($requestor_data['username'] ?? $requestor_data['email'] ?? '-') ?></dd></div>
                <div><dt>Room</dt><dd><?= e($rm['name'] ?? '-') ?></dd></div>
                <div><dt>Position</dt><dd><?= e($rm['position_code'] ?? '-') ?></dd></div>
                <div><dt>Capacity</dt><dd><?= e($rm['capacity'] ?? '-') ?></dd></div>
            </dl>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <h2>Online Access</h2>
            <?php if ($oa): ?>
            <dl class="meta">
                <div><dt>Platform</dt><dd><?= e($oa['platform'] ?? '-') ?></dd></div>
                <div><dt>Link</dt><dd><?= e($oa['meeting_link'] ?? $oa['link'] ?? '-') ?></dd></div>
                <div><dt>Access Key</dt><dd><?= e($oa['access_key'] ?? '-') ?></dd></div>
                <div><dt>Host Notes</dt><dd><?= e($oa['host_notes'] ?? '-') ?></dd></div>
            </dl>
            <?php else: ?>
                <div class="empty">No online access configured.</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Resources</h2>
            <?php if ($reqs): ?>
                <ul>
                <?php foreach ($reqs as $rq): $rq = is_object($rq) ? $rq->to_array() : $rq; ?>
                    <li><?= e($rq['resource_name'] ?? $rq['name'] ?? $rq['resource_id'] ?? '?') ?> x <?= e($rq['required_quantity'] ?? $rq['quantity'] ?? 1) ?></li>
                <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty">No resource requirements.</div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
