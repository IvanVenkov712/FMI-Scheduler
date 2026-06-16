<?php
require_once __DIR__ . '/../shared/ui.php';
$b = is_object($building ?? null) ? $building->to_array() : ($building ?? []);
page_start($b['name'] ?? 'Building Details', 'buildings');
?>

<div class="actions page-actions">
    <a href="/buildings/<?= e($b['id']) ?>/edit" class="btn">Edit</a>
    <a href="/buildings/<?= e($b['id']) ?>/vertical" class="btn btn-muted">Vertical View</a>
    <a href="/buildings/<?= e($b['id']) ?>/floors" class="btn btn-muted">Floors</a>
    <a href="/buildings" class="btn btn-muted">Back</a>
</div>

<div class="card">
    <h2>Details</h2>
    <dl class="meta">
        <div><dt>Code</dt><dd><?= e($b['code'] ?? '-') ?></dd></div>
        <div><dt>Name</dt><dd><?= e($b['name'] ?? '-') ?></dd></div>
        <div><dt>Address</dt><dd><?= e($b['address'] ?? '-') ?></dd></div>
        <div><dt>Campus Area</dt><dd><?= e($b['campus_area'] ?? '-') ?></dd></div>
        <div><dt>Floors</dt><dd><?= e($b['floor_count'] ?? '-') ?></dd></div>
        <div><dt>Status</dt><dd><?= status_badge(($b['is_active'] ?? 1) ? 'active' : 'inactive') ?></dd></div>
    </dl>
</div>

<?php page_end(); ?>
