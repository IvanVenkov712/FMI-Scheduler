<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Resources', 'resources'); ?>
<div class="card">
    <h2>Add Resource</h2>
    <form method="post" action="/resources" class="stack">
        <div class="fields">
            <div class="field"><label>Name</label><input type="text" name="name" required></div>
            <div class="field"><label>Description</label><input type="text" name="description"></div>
        </div>
        <button type="submit">Add</button>
    </form>
</div>
<div class="card">
    <h2>All Resources</h2>
    <?php if (empty($resources)): ?><div class="empty">No resources defined.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>ID</th><th>Name</th><th>Description</th></tr></thead>
        <tbody>
        <?php foreach ($resources as $r): $r = is_object($r) ? $r->to_array() : $r; ?>
        <tr><td><?= e($r['id']) ?></td><td><?= e($r['name'] ?? '-') ?></td><td><?= e($r['description'] ?? '-') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php page_end(); ?>
