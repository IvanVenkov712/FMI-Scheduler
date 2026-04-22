<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$stats = isset($stats) && is_array($stats) ? $stats : [];
$upcomingEvents = isset($upcoming_events) && is_array($upcoming_events) ? $upcoming_events : [];
$pendingRequests = isset($pending_requests) && is_array($pending_requests) ? $pending_requests : [];

ui_page_start('Dashboard', [
    'active' => 'dashboard',
    'subtitle' => 'Quick access to the main backend flows: room search, schedule checks, requests, allocation, and import/export.',
    'actions' => [
        ['href' => '/rooms/search', 'label' => 'Search Free Rooms'],
        ['href' => '/allocation/search', 'label' => 'Run Allocation Search'],
        ['href' => '/import-export/import', 'label' => 'Import / Export'],
    ],
]);
?>

<section class="panel">
    <h2>System Snapshot</h2>
    <div class="stats">
        <?php foreach ($stats as $label => $value): ?>
            <article class="stat">
                <strong><?= ui_escape((string) $value) ?></strong>
                <span><?= ui_escape(ucwords(str_replace('_', ' ', (string) $label))) ?></span>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Core Test Flows</h2>
        <p class="panel-copy">These pages cover the main project requirements around buildings, room planning, schedule management, requests, and routing.</p>
        <div class="pill-list">
            <a class="pill" href="/buildings">Buildings</a>
            <a class="pill" href="/floors">Floors</a>
            <a class="pill" href="/rooms">Rooms</a>
            <a class="pill" href="/schedules">Schedules</a>
            <a class="pill" href="/events">Events</a>
            <a class="pill" href="/requests">Requests</a>
            <a class="pill" href="/routes">Routes</a>
            <a class="pill" href="/resources">Resources</a>
            <a class="pill" href="/users/profile">Profile</a>
        </div>
    </article>

    <article class="panel">
        <h2>Quick Search</h2>
        <form method="get" action="/allocation/search" class="stack">
            <div class="fields">
                <div class="field">
                    <label for="capacity">Capacity</label>
                    <input type="number" id="capacity" name="capacity" min="0" value="30">
                </div>
                <div class="field">
                    <label for="delivery_mode">Delivery Mode</label>
                    <select id="delivery_mode" name="delivery_mode">
                        <option value="physical">Physical</option>
                        <option value="online">Online</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>
                <div class="field">
                    <label for="starts_at">Starts At</label>
                    <input type="datetime-local" id="starts_at" name="starts_at">
                </div>
                <div class="field">
                    <label for="ends_at">Ends At</label>
                    <input type="datetime-local" id="ends_at" name="ends_at">
                </div>
            </div>
            <button type="submit">Find Suitable Rooms</button>
        </form>
    </article>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Upcoming Events</h2>
        <?php if ($upcomingEvents === []): ?>
            <div class="empty">No upcoming events are currently scheduled.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Starts</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <tr>
                                <td>
                                    <a href="/events/<?= ui_escape((string) ($event['id'] ?? '')) ?>">
                                        <?= ui_escape((string) ($event['title'] ?? 'Untitled event')) ?>
                                    </a>
                                </td>
                                <td><?= ui_escape(ui_format_datetime($event['starts_at'] ?? null)) ?></td>
                                <td><?= ui_escape((string) ($event['room_code'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Pending Requests</h2>
        <?php if ($pendingRequests === []): ?>
            <div class="empty">No pending requests at the moment.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $request): ?>
                            <tr>
                                <td>
                                    <a href="/requests/<?= ui_escape((string) ($request['id'] ?? '')) ?>">
                                        <?= ui_escape((string) ($request['course_name'] ?? $request['title'] ?? 'Request')) ?>
                                    </a>
                                </td>
                                <td><span class="<?= ui_escape(ui_status_class($request['status'] ?? 'pending')) ?>"><?= ui_escape((string) ($request['status'] ?? 'pending')) ?></span></td>
                                <td><?= ui_escape(ui_format_datetime($request['created_at'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(); ?>
