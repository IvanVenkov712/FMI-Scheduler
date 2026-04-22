<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$schedule = isset($schedule) && is_array($schedule) ? $schedule : null;
$events = isset($events) && is_array($events) ? $events : [];

ui_page_start($schedule !== null ? 'Schedule Calendar' : 'Global Calendar', [
    'active' => 'schedules',
    'subtitle' => 'Chronological view of the events currently attached to the selected schedule.',
]);
?>

<section class="panel">
    <?php if ($schedule !== null): ?>
        <p class="panel-copy">Showing calendar for schedule <strong><?= ui_escape((string) ($schedule['name'] ?? '')) ?></strong>.</p>
    <?php endif; ?>

    <?php if ($events === []): ?>
        <div class="empty">No events are attached to this calendar yet.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Title</th><th>Starts</th><th>Ends</th><th>Room</th><th>Allocation</th></tr></thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><a href="/events/<?= ui_escape((string) ($event['id'] ?? '')) ?>"><?= ui_escape((string) ($event['title'] ?? '')) ?></a></td>
                            <td><?= ui_escape(ui_format_datetime($event['starts_at'] ?? null)) ?></td>
                            <td><?= ui_escape(ui_format_datetime($event['ends_at'] ?? null)) ?></td>
                            <td><?= ui_escape((string) ($event['room_code'] ?? '-')) ?></td>
                            <td><?= ui_escape((string) ($event['allocation_status'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php ui_page_end(); ?>
