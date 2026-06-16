<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Export Data', 'import'); ?>
<div class="grid-2">
    <div class="card">
        <h2>Export Schedule</h2>
        <form method="get" action="/import-export/export-schedule/0" class="stack" id="export-schedule-form">
            <div class="field"><label>Schedule</label>
                <select name="schedule_id" required id="export-schedule-id">
                    <option value="">Select schedule</option>
                    <?php foreach ($schedules ?? [] as $schedule): $schedule = is_object($schedule) ? $schedule->to_array() : $schedule; ?>
                    <option value="<?= e($schedule['id'] ?? '') ?>"><?= e($schedule['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Export</button>
        </form>
        <script>
        document.getElementById('export-schedule-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var id = document.getElementById('export-schedule-id').value;
            if (id) window.location.href = '/import-export/export-schedule/' + id;
        });
        </script>
    </div>
    <div class="card">
        <h2>Export Building</h2>
        <form method="get" action="/import-export/export-building/0" class="stack" id="export-building-form">
            <div class="field"><label>Building</label>
                <select name="building_id" required id="export-building-id">
                    <option value="">Select building</option>
                    <?php foreach ($buildings ?? [] as $building): $building = is_object($building) ? $building->to_array() : $building; ?>
                    <option value="<?= e($building['id'] ?? '') ?>"><?= e($building['name'] ?? '') ?><?= !empty($building['code']) ? ' (' . e($building['code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Export</button>
        </form>
        <script>
        document.getElementById('export-building-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var id = document.getElementById('export-building-id').value;
            if (id) window.location.href = '/import-export/export-building/' + id;
        });
        </script>
    </div>
</div>
<div class="grid-2">
    <div class="card">
        <h2>Export CSV</h2>
        <form method="get" action="/import-export/export-csv" class="stack">
            <div class="field"><label>Type</label>
                <select name="type" required>
                    <option value="">Select type</option>
                    <option value="buildings">Buildings</option>
                    <option value="floors">Floors</option>
                    <option value="rooms">Rooms</option>
                    <option value="resources">Resources</option>
                    <option value="events">Events</option>
                    <option value="schedules">Schedules</option>
                </select>
            </div>
            <button type="submit">Export CSV</button>
        </form>
    </div>
    <div class="card">
        <h2>Export XML</h2>
        <form method="get" action="/import-export/export-xml" class="stack">
            <button type="submit">Export XML</button>
        </form>
    </div>
</div>
<?php page_end(); ?>
