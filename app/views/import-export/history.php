<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'ui.php';

$jobs = isset($jobs) && is_array($jobs) ? $jobs : [];
$message = isset($message) ? (string) $message : '';
$result = isset($result) && is_array($result) ? $result : [];

ui_page_start('Import / Export History', [
    'active' => 'import-export',
    'subtitle' => 'Latest jobs and the most recent result payload from import/export actions.',
]);
?>

<?php ui_alert($message, 'success'); ?>

<section class="grid two">
    <article class="panel">
        <h2>Recent Jobs</h2>
        <?php if ($jobs === []): ?>
            <div class="empty">No jobs have been recorded yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Type</th><th>Entity</th><th>Status</th><th>Completed</th></tr></thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?= ui_escape((string) ($job['id'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($job['type'] ?? '-')) ?></td>
                                <td><?= ui_escape((string) ($job['entity'] ?? '-')) ?></td>
                                <td><span class="<?= ui_escape(ui_status_class($job['status'] ?? '')) ?>"><?= ui_escape((string) ($job['status'] ?? '-')) ?></span></td>
                                <td><?= ui_escape(ui_format_datetime($job['completed_at'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Latest Result</h2>
        <?php if ($result === []): ?>
            <div class="empty">No result payload to show.</div>
        <?php else: ?>
            <pre class="code"><?= ui_escape((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
        <?php endif; ?>
    </article>
</section>

<?php ui_page_end(); ?>
