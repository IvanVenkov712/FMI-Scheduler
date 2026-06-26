<?php
require_once __DIR__ . '/../shared/ui.php';

$room_options = [];
foreach ($rooms ?? [] as $room) {
    $room = is_object($room) ? $room->to_array() : $room;
    $room_options[(string) ($room['id'] ?? '')] = $room;
}
$room_label = static function ($room_id) use ($room_options) {
    $room = $room_options[(string) $room_id] ?? null;
    if ($room === null) {
        return 'Room #' . $room_id;
    }

    return ($room['name'] ?? 'Room #' . $room_id)
        . (!empty($room['position_code']) ? ' (' . $room['position_code'] . ')' : '');
};
$search = $route_search ?? [];
$items = $route_result ?? [];
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Route Export</title>
<link rel="stylesheet" href="/css/style.css">
<style>
body { background: #f6f7fb; color: #172033; }
.route-export-page { max-width: 900px; margin: 32px auto; padding: 0 18px 32px; }
.route-export-header { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; margin-bottom: 18px; }
.route-export-header h1 { margin: 0 0 6px; }
.route-export-meta { color: #5d667a; font-size: 0.95rem; }
@media print {
    body { background: #fff; }
    .route-export-page { margin: 0; max-width: none; padding: 0; }
    .no-print { display: none; }
    .card { box-shadow: none; border: 1px solid #d8dde8; }
}
</style>
</head>
<body>
<main class="route-export-page">
    <div class="route-export-header">
        <div>
            <h1>Route Export</h1>
            <div class="route-export-meta">Generated at <?= e($generated_at ?? date('Y-m-d H:i:s')) ?></div>
        </div>
        <div class="actions no-print">
            <button type="button" onclick="window.print()">Print</button>
            <a href="/routes" class="btn btn-muted">Back</a>
        </div>
    </div>

    <div class="card">
        <h2>Summary</h2>
        <dl class="meta">
            <div><dt>From Room</dt><dd><?= e($room_label($search['from_room_id'] ?? '')) ?></dd></div>
            <div><dt>To Room</dt><dd><?= e($room_label($search['to_room_id'] ?? '')) ?></dd></div>
            <div><dt>Total Distance</dt><dd><?= e($route_distance ?? 0) ?> m</dd></div>
            <div><dt>Accessible Only</dt><dd><?= !empty($search['accessible_only']) ? 'Yes' : 'No' ?></dd></div>
            <div><dt>Start Time</dt><dd><?= e($search['start_time'] ?? '-') ?></dd></div>
            <div><dt>End Time</dt><dd><?= e($search['end_time'] ?? '-') ?></dd></div>
        </dl>
    </div>

    <div class="card">
        <h2>Steps</h2>
        <?php if (empty($items)): ?>
            <div class="alert ok">Start and destination are the same room. Total distance: 0m.</div>
        <?php else: ?>
            <ol class="route-steps">
            <?php foreach ($items as $segment): $segment = is_object($segment) ? $segment->to_array() : $segment; ?>
                <li>
                    <strong><?= e($room_label($segment['from_room_id'] ?? '')) ?></strong>
                    <span>to <?= e($room_label($segment['to_room_id'] ?? '')) ?></span>
                    <span class="badge neutral"><?= e($segment['distance'] ?? 0) ?>m</span>
                    <?php if (!empty($segment['is_elevator'])): ?><span class="badge ok">Elevator</span><?php endif; ?>
                    <?php if (!empty($segment['is_stairs'])): ?><span class="badge warn">Stairs</span><?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
