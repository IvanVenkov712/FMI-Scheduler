<?php
require_once __DIR__ . '/../shared/ui.php';

$s = is_object($schedule ?? null) ? $schedule->to_array() : ($schedule ?? []);
$evs = $events ?? [];
$room_options = [];
foreach ($rooms ?? [] as $room) {
    $room = is_object($room) ? $room->to_array() : $room;
    $room_options[(string) ($room['id'] ?? '')] = $room;
}
$room_label = static function ($room_id) use ($room_options) {
    if ($room_id === null || trim((string) $room_id) === '') {
        return 'N/A';
    }

    $room = $room_options[(string) $room_id] ?? null;
    if ($room === null) {
        return 'Room #' . $room_id;
    }

    return ($room['name'] ?? 'Room #' . $room_id)
        . (!empty($room['position_code']) ? ' (' . $room['position_code'] . ')' : '');
};
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Schedule Export</title>
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
            <h1>Schedule Export</h1>
            <div class="static-export-meta">Generated at <?= e($generated_at ?? date('Y-m-d H:i:s')) ?></div>
        </div>
        <div class="actions no-print">
            <button type="button" onclick="window.print()">Print</button>
            <a href="/schedules/<?= e($s['id'] ?? '') ?>" class="btn btn-muted">Back</a>
        </div>
    </div>

    <div class="card">
        <h2>Schedule Info</h2>
        <dl class="meta">
            <div><dt>Name</dt><dd><?= e($s['name'] ?? '-') ?></dd></div>
            <div><dt>Description</dt><dd><?= e($s['description'] ?? '-') ?></dd></div>
            <div><dt>Start</dt><dd><?= fmt_dt($s['start_date'] ?? null) ?></dd></div>
            <div><dt>End</dt><dd><?= fmt_dt($s['end_date'] ?? null) ?></dd></div>
            <div><dt>Events</dt><dd><?= count($evs) ?></dd></div>
        </dl>
    </div>

    <div class="card">
        <h2>Events</h2>
        <?php if (empty($evs)): ?>
            <div class="empty">No events in this schedule.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Name</th><th>Subject</th><th>Time</th><th>Room</th><th>Mode</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($evs as $ev): $ev = is_object($ev) ? $ev->to_array() : $ev; ?>
                    <tr>
                        <td><?= e($ev['name'] ?? '-') ?></td>
                        <td><?= e($ev['subject_name'] ?? '-') ?></td>
                        <td><?= fmt_dt($ev['start_time'] ?? null) ?> - <?= fmt_dt($ev['end_time'] ?? null) ?></td>
                        <td><?= e($room_label($ev['room_id'] ?? null)) ?></td>
                        <td><?= e($ev['delivery_mode'] ?? '-') ?></td>
                        <td><?= status_badge($ev['status'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
