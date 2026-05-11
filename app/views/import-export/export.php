<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Export Data', 'import'); ?>
<div class="grid-2">
    <div class="card">
        <h2>Export Schedule</h2>
        <form method="get" action="/import-export/export-schedule/0" class="stack" id="export-schedule-form">
            <div class="field"><label>Schedule ID</label><input type="number" name="schedule_id" required id="export-schedule-id"></div>
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
            <div class="field"><label>Building ID</label><input type="number" name="building_id" required id="export-building-id"></div>
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
<?php page_end(); ?>
