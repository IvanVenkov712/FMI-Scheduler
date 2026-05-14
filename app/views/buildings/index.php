<?php require_once __DIR__ . '/../shared/ui.php'; page_start('Buildings', 'buildings'); ?>

<div class="actions page-actions">
    <a href="/buildings/create" class="btn">New Building</a>
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
