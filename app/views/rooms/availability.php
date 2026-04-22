<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$room = isset($room) && is_array($room) ? $room : [];
$events = isset($events) && is_array($events) ? $events : [];
$unavailability = isset($unavailability) && is_array($unavailability) ? $unavailability : [];
$from = isset($from) ? (string) $from : '';
$to = isset($to) ? (string) $to : '';

ui_page_start('Room Availability', [
    'active' => 'rooms',
    'subtitle' => 'Check scheduled events and explicit unavailability intervals for a room.',
]);
?>

<section class="panel">
    <form method="get" class="stack">
        <div class="fields">
            <div class="field">
                <label for="from">From</label>
                <input type="datetime-local" id="from" name="from" value="<?= ui_escape(str_replace(' ', 'T', substr($from, 0, 16))) ?>">
            </div>
            <div class="field">
                <label for="to">To</label>
                <input type="datetime-local" id="to" name="to" value="<?= ui_escape(str_replace(' ', 'T', substr($to, 0, 16))) ?>">
            </div>
        </div>
        <div class="actions-bar">
            <button type="submit">Refresh Availability</button>
            <a class="button button-link button-muted" href="/rooms/<?= ui_escape((string) ($room['id'] ?? '')) ?>">Back to Room</a>
        </div>
    </form>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Scheduled Events</h2>
        <?php if ($events === []): ?>
            <div class="empty">No event conflicts found in the selected window.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Title</th><th>Starts</th><th>Ends</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?= ui_escape((string) ($event['title'] ?? '-')) ?></td>
                                <td><?= ui_escape(ui_format_datetime($event['starts_at'] ?? null)) ?></td>
                                <td><?= ui_escape(ui_format_datetime($event['ends_at'] ?? null)) ?></td>
                                <td><?= ui_escape((string) ($event['status'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Room Blocks</h2>
        <?php if ($unavailability === []): ?>
            <div class="empty">No manual room unavailability records found.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Reason</th><th>Starts</th><th>Ends</th></tr></thead>
                    <tbody>
                        <?php foreach ($unavailability as $entry): ?>
                            <tr>
                                <td><?= ui_escape((string) ($entry['reason'] ?? $entry['notes'] ?? '-')) ?></td>
                                <td><?= ui_escape(ui_format_datetime($entry['starts_at'] ?? null)) ?></td>
                                <td><?= ui_escape(ui_format_datetime($entry['ends_at'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(); ?>
