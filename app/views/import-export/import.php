<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Import Data', 'import'); ?>
<div class="grid-2">
    <div class="card">
        <h2>Import CSV</h2>
        <form method="post" action="/import-export/import-csv" class="stack" enctype="multipart/form-data">
            <div class="field"><label>Type</label>
                <select name="type"><option value="rooms">Rooms</option><option value="events">Events</option><option value="schedules">Schedules</option><option value="resources">Resources</option></select>
            </div>
            <div class="field"><label>CSV File</label><input type="file" name="file" accept=".csv" required></div>
            <button type="submit">Import CSV</button>
        </form>
    </div>
    <div class="card">
        <h2>Import XML</h2>
        <form method="post" action="/import-export/import-xml" class="stack" enctype="multipart/form-data">
            <div class="field"><label>XML File</label><input type="file" name="file" accept=".xml"></div>
            <div class="field"><label>Or paste XML</label><textarea name="xml" rows="6" placeholder="Paste XML here"></textarea></div>
            <button type="submit">Import XML</button>
        </form>
    </div>
</div>
<div class="card">
    <h2>Import ASCII Floor Plan</h2>
    <form method="post" action="/import-export/import-ascii-plan" class="stack">
        <div class="fields">
            <div class="field"><label>Floor ID</label><input type="number" name="floor_id" required></div>
        </div>
        <div class="field full"><label>ASCII Plan</label><textarea name="ascii_plan" rows="12" class="ascii-input" required placeholder="Paste your ASCII floor plan here..."></textarea></div>
        <button type="submit">Import Plan</button>
    </form>
</div>
<?php page_end(); ?>
