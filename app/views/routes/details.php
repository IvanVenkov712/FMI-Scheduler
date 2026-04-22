<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$route = isset($route) && is_array($route) ? $route : [];
$steps = isset($steps) && is_array($steps) ? $steps : [];

ui_page_start((string) ($route['name'] ?? 'Route Details'), [
    'active' => 'routes',
    'subtitle' => 'Inspect route durations and the stored ordered steps between locations.',
]);
?>

<section class="grid two">
    <article class="panel">
        <h2>Overview</h2>
        <dl class="meta">
            <div><dt>From Room</dt><dd><?= ui_escape((string) ($route['from_room_id'] ?? '-')) ?></dd></div>
            <div><dt>To Room</dt><dd><?= ui_escape((string) ($route['to_room_id'] ?? '-')) ?></dd></div>
            <div><dt>From Building</dt><dd><?= ui_escape((string) ($route['from_building_id'] ?? '-')) ?></dd></div>
            <div><dt>To Building</dt><dd><?= ui_escape((string) ($route['to_building_id'] ?? '-')) ?></dd></div>
            <div><dt>Walking</dt><dd><?= ui_escape((string) ($route['walking_duration_minutes'] ?? '-')) ?> min</dd></div>
            <div><dt>Car</dt><dd><?= ui_escape((string) ($route['car_duration_minutes'] ?? '-')) ?> min</dd></div>
            <div><dt>Public Transport</dt><dd><?= ui_escape((string) ($route['public_transport_duration_minutes'] ?? '-')) ?> min</dd></div>
            <div><dt>Accessible</dt><dd><?= ui_escape(ui_bool_label($route['is_accessible'] ?? 0)) ?></dd></div>
            <div><dt>Bidirectional</dt><dd><?= ui_escape(ui_bool_label($route['is_bidirectional'] ?? 0)) ?></dd></div>
        </dl>
    </article>

    <article class="panel">
        <h2>Steps</h2>
        <?php if ($steps === []): ?>
            <div class="empty">No explicit route steps are stored for this route.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Order</th><th>Type</th><th>Label</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach ($steps as $step): ?>
                            <tr>
                                <td><?= ui_escape((string) ($step['step_order'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($step['step_type'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($step['label'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($step['notes'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(); ?>
