<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'View.php';

$stats = isset($stats) && is_array($stats) ? $stats : [];
$upcoming_events = isset($upcoming_events) && is_array($upcoming_events) ? $upcoming_events : [];
$pending_requests = isset($pending_requests) && is_array($pending_requests) ? $pending_requests : [];

$statLabels = [
    'buildings' => 'Buildings',
    'floors' => 'Floors',
    'rooms' => 'Rooms',
    'events' => 'Events',
    'schedules' => 'Schedules',
    'exam_requests' => 'Exam requests',
    'routes' => 'Routes',
];

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return 'Not scheduled';
    }

    try {
        return (new DateTimeImmutable($value))->format('d.m.Y H:i');
    } catch (Throwable) {
        return $value;
    }
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Schedule Management System</title>
    <style>
        :root {
            --page: #f4f7f2;
            --panel: #ffffff;
            --ink: #163126;
            --muted: #587062;
            --line: #dbe5de;
            --accent: #1d6b52;
            --accent-soft: #ebf5f0;
            --secondary: #215f86;
            --warning: #8a5a14;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(29, 107, 82, 0.10), transparent 28%),
                linear-gradient(180deg, #f8fbf9 0%, var(--page) 100%);
        }

        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .hero {
            display: grid;
            gap: 16px;
            padding: 28px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(29, 107, 82, 0.08), rgba(33, 95, 134, 0.08)), var(--panel);
            box-shadow: 0 20px 45px rgba(22, 49, 38, 0.08);
        }

        .eyebrow {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--secondary);
        }

        h1 {
            margin: 0;
            font-size: clamp(30px, 6vw, 48px);
            line-height: 1.08;
        }

        .lead {
            max-width: 780px;
            margin: 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 14px;
            margin-top: 24px;
        }

        .stat {
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.86);
        }

        .stat strong {
            display: block;
            font-size: 28px;
            margin-bottom: 6px;
        }

        .stat span {
            color: var(--muted);
            font-size: 14px;
        }

        .content {
            display: grid;
            grid-template-columns: 1.3fr 0.9fr;
            gap: 20px;
            margin-top: 24px;
        }

        .panel {
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
        }

        .panel h2 {
            margin: 0 0 14px;
            font-size: 22px;
        }

        .panel p {
            color: var(--muted);
        }

        .list {
            display: grid;
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .item {
            padding: 14px 16px;
            border-radius: 12px;
            background: #f8fbf9;
            border: 1px solid var(--line);
        }

        .item strong {
            display: block;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .meta {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .empty {
            padding: 16px;
            border-radius: 12px;
            background: var(--accent-soft);
            color: var(--ink);
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: #fff4df;
            color: var(--warning);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        @media (max-width: 900px) {
            .content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <div class="eyebrow">Schedule Management System</div>
            <h1>Operational overview for rooms, schedules, and exam planning.</h1>
            <p class="lead">
                The application is running and the dashboard route is now rendering correctly.
                From here you can inspect current capacity, upcoming activity, and pending approval work.
            </p>

            <div class="stats" aria-label="System statistics">
                <?php foreach ($statLabels as $key => $label): ?>
                    <article class="stat">
                        <strong><?= View::escape((string) ($stats[$key] ?? 0)) ?></strong>
                        <span><?= View::escape($label) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content">
            <article class="panel">
                <h2>Upcoming events</h2>

                <?php if ($upcoming_events === []): ?>
                    <div class="empty">There are no upcoming events to display yet.</div>
                <?php else: ?>
                    <ul class="list">
                        <?php foreach ($upcoming_events as $event): ?>
                            <li class="item">
                                <strong><?= View::escape((string) ($event['title'] ?? 'Untitled event')) ?></strong>
                                <div class="meta">
                                    Starts: <?= View::escape($formatDate($event['starts_at'] ?? null)) ?>
                                </div>
                                <div class="meta">
                                    Room: <?= View::escape((string) ($event['room_code'] ?? 'Unknown room')) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>

            <article class="panel">
                <h2>Pending requests</h2>

                <?php if ($pending_requests === []): ?>
                    <div class="empty">There are no pending exam requests right now.</div>
                <?php else: ?>
                    <ul class="list">
                        <?php foreach ($pending_requests as $request): ?>
                            <li class="item">
                                <span class="status"><?= View::escape((string) ($request['status'] ?? 'pending')) ?></span>
                                <strong><?= View::escape((string) ($request['title'] ?? $request['subject'] ?? 'Request')) ?></strong>
                                <div class="meta">
                                    Submitted: <?= View::escape($formatDate($request['created_at'] ?? null)) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        </section>
    </main>
</body>
</html>
