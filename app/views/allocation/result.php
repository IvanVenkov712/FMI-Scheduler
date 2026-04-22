<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$run = isset($run) && is_array($run) ? $run : [];
$result = isset($result) && is_array($result) ? $result : [];
$assignments = isset($result['assignments']) && is_array($result['assignments']) ? $result['assignments'] : [];
$conflicts = isset($result['conflicts']) && is_array($result['conflicts']) ? $result['conflicts'] : [];
$summary = isset($result['summary']) && is_array($result['summary']) ? $result['summary'] : [];

ui_page_start('Allocation Result', [
    'active' => 'allocation',
    'subtitle' => 'Review the latest auto-assignment run, successful placements, and unresolved conflicts.',
]);
?>

<section class="panel">
    <div class="stats">
        <?php foreach ($summary as $label => $value): ?>
            <article class="stat">
                <strong><?= ui_escape((string) $value) ?></strong>
                <span><?= ui_escape(ucwords(str_replace('_', ' ', (string) $label))) ?></span>
            </article>
        <?php endforeach; ?>
        <article class="stat">
            <strong><?= ui_escape((string) ($run['id'] ?? '-')) ?></strong>
            <span>Run ID</span>
        </article>
    </div>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Assignments</h2>
        <?php if ($assignments === []): ?>
            <div class="empty">No events were assigned during this run.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Event ID</th><th>Room</th><th>Score</th><th>Explanation</th></tr></thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?= ui_escape((string) ($assignment['event_id'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($assignment['room_code'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($assignment['score'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($assignment['explanation'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Conflicts</h2>
        <?php if ($conflicts === []): ?>
            <div class="empty">No conflicts were reported for this run.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Event</th><th>Reason</th><th>Alternatives</th></tr></thead>
                    <tbody>
                        <?php foreach ($conflicts as $conflict): ?>
                            <tr>
                                <td><?= ui_escape((string) (($conflict['title'] ?? '') . ' (#' . ($conflict['event_id'] ?? '-') . ')')) ?></td>
                                <td><?= ui_escape((string) ($conflict['reason'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) json_encode($conflict['alternatives'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(); ?>
