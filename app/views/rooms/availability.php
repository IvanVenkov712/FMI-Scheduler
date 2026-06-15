<?php
require_once __DIR__ . '/../shared/ui.php';
$r = is_object($room ?? null) ? $room->to_array() : ($room ?? []);
page_start('Room Availability', 'rooms');
?>
<div class="card">
    <h2>Check Availability: <?= e($r['name'] ?? 'Room') ?></h2>
    <form method="post" action="/rooms/<?= e($r['id'] ?? '') ?>/availability" class="stack">
        <div class="fields">
            <div class="field"><label>Start Time</label><input type="datetime-local" name="start_time" required></div>
            <div class="field"><label>End Time</label><input type="datetime-local" name="end_time" required></div>
            <div class="field"><label>Students</label><input type="number" name="student_count" value="30" min="0" step="1"></div>
        </div>
        <button type="submit">Check</button>
    </form>
</div>
<?php if (isset($bookable)): ?>
<div class="card">
    <div class="alert <?= $bookable ? 'ok' : 'err' ?>">
        <?= $bookable ? 'Room is available for the requested time.' : 'Room is NOT available. See conflicts below.' ?>
    </div>
    <?php if (!empty($conflicts)): ?>
    <table>
        <thead><tr><th>Conflict</th><th>Details</th></tr></thead>
        <tbody>
        <?php foreach ($conflicts as $c): $c = is_array($c) ? $c : ['reason' => (string)$c]; ?>
        <tr><td><?= e($c['reason'] ?? $c['type'] ?? '-') ?></td><td><?= e($c['details'] ?? $c['event_name'] ?? '-') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php page_end(); ?>
