<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Dashboard', 'dashboard'); ?>

<div class="card">
    <h2>System Overview</h2>
    <div class="stats">
        <?php foreach (($stats ?? []) as $label => $value): ?>
        <div class="stat"><strong><?= e($value) ?></strong><span><?= e(ucfirst($label)) ?></span></div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2>Recent Events</h2>
    <?php if (empty($events)): ?>
        <div class="empty">No events yet.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>Name</th><th>Type</th><th>Time</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($events as $ev):
            $ev = is_object($ev) ? $ev->to_array() : $ev;
        ?>
        <tr>
            <td><a href="/events/<?= e($ev['id']) ?>"><?= e($ev['name'] ?? $ev['subject_name'] ?? '-') ?></a></td>
            <td><?= e($ev['type'] ?? '-') ?></td>
            <td><?= fmt_dt($ev['start_time'] ?? null) ?></td>
            <td><?= status_badge($ev['status'] ?? '') ?></td>
            <td><a href="/events/<?= e($ev['id']) ?>" class="btn btn-muted btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Quick Links</h2>
        <div class="pills">
            <a class="pill" href="/buildings">Buildings</a>
            <a class="pill" href="/rooms">Rooms</a>
            <a class="pill" href="/schedules">Schedules</a>
            <a class="pill" href="/requests">Requests</a>
            <a class="pill" href="/allocation/search">Find Room</a>
            <a class="pill" href="/import-export/import">Import/Export</a>
        </div>
    </div>
    <div class="card">
        <h2>Quick Room Search</h2>
        <form method="post" action="/allocation/search" class="stack">
            <div class="fields">
                <div class="field"><label>Students</label><input type="number" name="student_count" value="30"></div>
                <div class="field"><label>Mode</label>
                    <select name="delivery_mode"><option value="physical">Physical</option><option value="online">Online</option><option value="hybrid">Hybrid</option></select>
                </div>
            </div>
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<?php page_end(); ?>
