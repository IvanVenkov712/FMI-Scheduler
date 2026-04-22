<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$resources = isset($resources) && is_array($resources) ? $resources : [];
$roomResources = isset($room_resources) && is_array($room_resources) ? $room_resources : [];
$roomId = isset($room_id) ? (string) $room_id : '';

ui_page_start('Manage Room Resources', [
    'active' => 'resources',
    'subtitle' => 'Assign resources to a room and inspect the current room inventory snapshot.',
]);
?>

<section class="grid two">
    <article class="panel">
        <h2>Assign Resource</h2>
        <form method="post" class="stack">
            <div class="fields">
                <div class="field">
                    <label for="room_id">Room ID</label>
                    <input type="number" id="room_id" name="room_id" value="<?= ui_escape($roomId) ?>">
                </div>
                <div class="field">
                    <label for="resource_id">Resource</label>
                    <select id="resource_id" name="resource_id">
                        <?php foreach ($resources as $resource): ?>
                            <option value="<?= ui_escape((string) ($resource['id'] ?? '')) ?>"><?= ui_escape((string) ($resource['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" value="1">
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <input type="text" id="status" name="status" value="available">
                </div>
                <div class="field full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4"></textarea>
                </div>
            </div>
            <button type="submit">Save Assignment</button>
        </form>
    </article>

    <article class="panel">
        <h2>Current Room Resources</h2>
        <?php if ($roomResources === []): ?>
            <div class="empty">Select a room ID in the URL or submit the form to inspect room resources.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Resource</th><th>Quantity</th><th>Status</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach ($roomResources as $entry): ?>
                            <tr>
                                <td><?= ui_escape((string) ($entry['name'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($entry['quantity'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($entry['status'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($entry['notes'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(); ?>
