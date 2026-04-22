<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$resourceRequirements = isset($filters['resourceRequirements']) && is_array($filters['resourceRequirements']) ? $filters['resourceRequirements'] : [];

ui_page_start('Allocation Search', [
    'active' => 'allocation',
    'subtitle' => 'Search for the best available rooms based on time, capacity, delivery mode, and required resources.',
]);
?>

<section class="panel">
    <form method="get" class="stack">
        <div class="fields">
            <div class="field">
                <label for="capacity">Capacity</label>
                <input type="number" id="capacity" name="capacity" value="<?= ui_escape((string) ($filters['capacity'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="delivery_mode">Delivery Mode</label>
                <select id="delivery_mode" name="delivery_mode">
                    <?php foreach (['physical', 'online', 'hybrid'] as $mode): ?>
                        <option value="<?= ui_escape($mode) ?>" <?= (string) ($filters['deliveryMode'] ?? 'physical') === $mode ? 'selected' : '' ?>><?= ui_escape(ucfirst($mode)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="starts_at">Starts At</label>
                <input type="datetime-local" id="starts_at" name="starts_at" value="<?= ui_escape(str_replace(' ', 'T', substr((string) ($filters['startsAt'] ?? ''), 0, 16))) ?>">
            </div>
            <div class="field">
                <label for="ends_at">Ends At</label>
                <input type="datetime-local" id="ends_at" name="ends_at" value="<?= ui_escape(str_replace(' ', 'T', substr((string) ($filters['endsAt'] ?? ''), 0, 16))) ?>">
            </div>
            <div class="field full">
                <label for="resources">Resource IDs (comma separated)</label>
                <input type="text" id="resources" name="resources" value="<?= ui_escape(implode(',', array_map(static fn (array $item): string => (string) ($item['resource_id'] ?? ''), $resourceRequirements))) ?>">
            </div>
            <div class="field full">
                <label for="resource_quantities">Resource Quantities (comma separated)</label>
                <input type="text" id="resource_quantities" name="resource_quantities" value="<?= ui_escape(implode(',', array_map(static fn (array $item): string => (string) ($item['quantity'] ?? 1), $resourceRequirements))) ?>">
            </div>
        </div>
        <button type="submit">Search</button>
    </form>
</section>

<section class="panel">
    <h2>Ranked Rooms</h2>
    <?php if ($rooms === []): ?>
        <div class="empty">Submit search criteria to see ranked room candidates.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Room</th><th>Score</th><th>Explanation</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($rooms as $entry): ?>
                        <?php $room = is_array($entry['room'] ?? null) ? $entry['room'] : []; ?>
                        <tr>
                            <td><?= ui_escape((string) (($room['code'] ?? '') . ' ' . ($room['name'] ?? ''))) ?></td>
                            <td><?= ui_escape((string) ($entry['score'] ?? '-')) ?></td>
                            <td><?= ui_escape((string) ($entry['explanation'] ?? '-')) ?></td>
                            <td><a class="button button-link button-muted" href="/rooms/<?= ui_escape((string) ($room['id'] ?? '')) ?>">Room</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php ui_page_end(); ?>
