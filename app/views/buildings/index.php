<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Buildings', 'buildings'); ?>

<div class="actions page-actions">
    <a href="/buildings/create" class="btn">New Building</a>
</div>

<div class="card">
    <h2>Filters</h2>
    <form method="get" action="/buildings" class="stack">
        <div class="fields">
            <div class="field"><label>Search</label><input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Name, code, address"></div>
            <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($filters['name'] ?? '') ?>"></div>
            <div class="field"><label>Code</label><input type="text" name="code" value="<?= e($filters['code'] ?? '') ?>"></div>
            <div class="field"><label>Status</label>
                <select name="status">
                    <option value="">Any status</option>
                    <option value="active"<?= selected_attr('active', $filters['status'] ?? '') ?>>Active</option>
                    <option value="inactive"<?= selected_attr('inactive', $filters['status'] ?? '') ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="actions">
            <button type="submit">Filter</button>
            <a href="/buildings" class="btn btn-muted">Clear</a>
        </div>
    </form>
</div>

<div class="card">
    <?php if (empty($buildings)): ?>
        <div class="empty">No buildings found.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>Code</th><th>Name</th><th>Address</th><th>Floors</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($buildings as $b):
            $b = is_object($b) ? $b->to_array() : $b;
        ?>
        <tr>
            <td><?= e($b['code']) ?></td>
            <td><a href="/buildings/<?= e($b['id']) ?>"><?= e($b['name']) ?></a></td>
            <td><?= e($b['address'] ?? '-') ?></td>
            <td><?= e($b['floor_count'] ?? '-') ?></td>
            <td><?= status_badge($b['is_active'] ? 'active' : 'inactive') ?></td>
            <td class="actions">
                <a href="/buildings/<?= e($b['id']) ?>" class="btn btn-muted btn-sm">View</a>
                <a href="/buildings/<?= e($b['id']) ?>/vertical" class="btn btn-muted btn-sm">Diagram</a>
                <a href="/buildings/<?= e($b['id']) ?>/edit" class="btn btn-muted btn-sm">Edit</a>
                <form method="post" action="/buildings/<?= e($b['id']) ?>/delete" data-confirm="Delete this building?"><button class="btn btn-danger btn-sm">Del</button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php page_end(); ?>
