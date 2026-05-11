<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$route = isset($route) && is_array($route) ? $route : null;
$routes = isset($routes) && is_array($routes) ? $routes : [];
$buildings = isset($buildings) && is_array($buildings) ? $buildings : [];
$steps = isset($steps) && is_array($steps) ? $steps : [];

ui_page_start('Route Map', [
    'active' => 'routes',
    'subtitle' => 'A compact overview of defined routes and available building endpoints.',
]);
?>

<section class="panel">
    <h2>Campus Route Map</h2>
    <div id="route-map-svg" style="min-height:300px;"></div>
    <script id="route-map-data" type="application/json"><?= json_encode([
        'buildings' => $buildings,
        'routes' => $routes,
        'steps' => $steps,
        'highlightRouteId' => $route !== null ? ($route['id'] ?? null) : null,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</section>

<section class="grid two">
    <article class="panel">
        <h2><?= $route !== null ? 'Selected Route' : 'Available Routes' ?></h2>
        <?php if ($routes === []): ?>
            <div class="empty">No routes defined yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>From</th><th>To</th><th>Walking</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($routes as $entry): ?>
                            <tr>
                                <td><a href="/routes/<?= ui_escape((string) ($entry['id'] ?? '')) ?>"><?= ui_escape((string) ($entry['name'] ?? '')) ?></a></td>
                                <td><?= ui_escape((string) ($entry['from_room_id'] ?? $entry['from_building_id'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($entry['to_room_id'] ?? $entry['to_building_id'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($entry['walking_duration_minutes'] ?? '-')) ?> min</td>
                                <td><span class="<?= ui_escape(ui_status_class($entry['status'] ?? '')) ?>"><?= ui_escape((string) ($entry['status'] ?? '-')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Buildings</h2>
        <?php if ($buildings === []): ?>
            <div class="empty">No buildings available.</div>
        <?php else: ?>
            <div class="pill-list">
                <?php foreach ($buildings as $building): ?>
                    <a class="pill" href="/buildings/<?= ui_escape((string) ($building['id'] ?? '')) ?>"><?= ui_escape((string) (($building['code'] ?? '') . ' - ' . ($building['name'] ?? ''))) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($route !== null): ?>
            <div style="margin-top:18px;">
                <h3>Route Steps</h3>
                <div id="route-steps-list"></div>
            </div>
            <?php if (!empty($route['path_data'])): ?>
                <h3 style="margin-top:18px;">Stored Path Data</h3>
                <pre class="code"><?= ui_escape((string) $route['path_data']) ?></pre>
            <?php endif; ?>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(['route-map.js']); ?>
