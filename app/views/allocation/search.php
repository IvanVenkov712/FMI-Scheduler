<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Allocation Search', 'allocation'); ?>
<div class="card">
    <h2>Find Available Rooms</h2>
    <form method="post" action="/allocation/search" class="stack">
        <div class="fields">
            <div class="field"><label>Start Time</label><input type="datetime-local" name="start_time" required></div>
            <div class="field"><label>End Time</label><input type="datetime-local" name="end_time" required></div>
            <div class="field"><label>Students</label><input type="number" name="student_count" value="30" min="0" step="1"></div>
            <div class="field"><label>Delivery Mode</label>
                <select name="delivery_mode"><option value="physical">Physical</option><option value="online">Online</option><option value="hybrid">Hybrid</option></select>
            </div>
        </div>
        <button type="submit">Search</button>
    </form>
</div>

<?php if (isset($rooms) && is_array($rooms)): ?>
<div class="card">
    <h2>Results (<?= count($rooms) ?>)</h2>
    <?php if (empty($rooms)): ?><div class="empty">No suitable rooms found.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>Room</th><th>Capacity</th><th>Type</th><th>Score</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rooms as $r): $r = is_object($r) ? $r->to_array() : $r; ?>
        <tr>
            <td><a href="/rooms/<?= e($r['id'] ?? $r['room_id'] ?? '') ?>"><?= e($r['name'] ?? $r['room_name'] ?? '-') ?></a></td>
            <td><?= e($r['capacity'] ?? '-') ?></td>
            <td><?= e($r['room_type'] ?? '-') ?></td>
            <td><strong><?= e($r['score'] ?? '-') ?></strong></td>
            <td><a href="/rooms/<?= e($r['id'] ?? $r['room_id'] ?? '') ?>" class="btn btn-muted btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php page_end(); ?>
