<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$route = isset($route) && is_array($route) ? $route : null;
$routes = isset($routes) && is_array($routes) ? $routes : [];
$buildings = isset($buildings) && is_array($buildings) ? $buildings : [];

ui_page_start('Route Map', [
    'active' => 'routes',
    'subtitle' => 'A compact overview of defined routes and available building endpoints.',
]);
?>

<section class="grid two">
    <article class="panel">
        <h2><?= $route !== null ? 'Selected Route' : 'Available Routes' ?></h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>From</th><th>To</th><th>Walking</th></tr></thead>
                <tbody>
                    <?php foreach ($routes as $entry): ?>
                        <tr>
                            <td><a href="/routes/<?= ui_escape((string) ($entry['id'] ?? '')) ?>"><?= ui_escape((string) ($entry['name'] ?? '')) ?></a></td>
                            <td><?= ui_escape((string) ($entry['from_room_id'] ?? $entry['from_building_id'] ?? '-')) ?></td>
                            <td><?= ui_escape((string) ($entry['to_room_id'] ?? $entry['to_building_id'] ?? '-')) ?></td>
                            <td><?= ui_escape((string) ($entry['walking_duration_minutes'] ?? '-')) ?> min</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <h2>Buildings</h2>
        <?php if ($buildings === []): ?>
            <div class="empty">No buildings available.</div>
        <?php else: ?>
            <div class="pill-list">
                <?php foreach ($buildings as $building): ?>
                    <span class="pill"><?= ui_escape((string) (($building['code'] ?? '') . ' - ' . ($building['name'] ?? ''))) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($route !== null && !empty($route['path_data'])): ?>
            <h3 style="margin-top:18px;">Stored Path Data</h3>
            <pre class="code"><?= ui_escape((string) $route['path_data']) ?></pre>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(); ?>
