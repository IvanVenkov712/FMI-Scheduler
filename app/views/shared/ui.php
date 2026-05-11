<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'View.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'SessionHelper.php';

function ui_escape(mixed $value): string
{
    return View::escape((string) $value);
}

function ui_user(): ?array
{
    return SessionHelper::user();
}

function ui_format_datetime(mixed $value, string $fallback = '-'): string
{
    if ($value === null || $value === '') {
        return $fallback;
    }

    try {
        return (new DateTimeImmutable((string) $value))->format('d.m.Y H:i');
    } catch (Throwable) {
        return (string) $value;
    }
}

function ui_format_date(mixed $value, string $fallback = '-'): string
{
    if ($value === null || $value === '') {
        return $fallback;
    }

    try {
        return (new DateTimeImmutable((string) $value))->format('d.m.Y');
    } catch (Throwable) {
        return (string) $value;
    }
}

function ui_nav_items(): array
{
    return [
        'dashboard' => ['label' => 'Dashboard', 'href' => '/dashboard'],
        'buildings' => ['label' => 'Buildings', 'href' => '/buildings'],
        'floors' => ['label' => 'Floors', 'href' => '/floors'],
        'rooms' => ['label' => 'Rooms', 'href' => '/rooms'],
        'schedules' => ['label' => 'Schedules', 'href' => '/schedules'],
        'events' => ['label' => 'Events', 'href' => '/events'],
        'requests' => ['label' => 'Requests', 'href' => '/requests'],
        'resources' => ['label' => 'Resources', 'href' => '/resources'],
        'routes' => ['label' => 'Routes', 'href' => '/routes'],
        'allocation' => ['label' => 'Allocation', 'href' => '/allocation/search'],
        'import-export' => ['label' => 'Import/Export', 'href' => '/import-export/import'],
        'users' => ['label' => 'Users', 'href' => '/users'],
    ];
}

function ui_status_class(mixed $value): string
{
    $value = strtolower(trim((string) $value));

    return match ($value) {
        'active', 'approved', 'available', 'completed', 'auto_assigned' => 'badge badge-success',
        'pending', 'manual', 'draft', 'processing' => 'badge badge-warning',
        'cancelled', 'inactive', 'rejected', 'failed', 'conflict', 'blocked', 'unavailable' => 'badge badge-danger',
        default => 'badge badge-neutral',
    };
}

function ui_bool_label(mixed $value): string
{
    return in_array($value, [1, '1', true, 'true', 'yes', 'on'], true) ? 'Yes' : 'No';
}

function ui_page_start(string $title, array $options = []): void
{
    $active = (string) ($options['active'] ?? 'dashboard');
    $subtitle = (string) ($options['subtitle'] ?? '');
    $actions = is_array($options['actions'] ?? null) ? $options['actions'] : [];
    $flashSuccess = SessionHelper::getFlash('success');
    $flashError = SessionHelper::getFlash('error');
    $navItems = ui_nav_items();
    $user = ui_user();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ui_escape($title) ?> | Schedule Management</title>
    <style>
        :root {
            --page: #f3f5ef;
            --page-alt: #fbfcf8;
            --panel: #ffffff;
            --ink: #17261f;
            --muted: #627468;
            --line: #d8dfd3;
            --accent: #22543d;
            --accent-strong: #173c2c;
            --accent-soft: #e6f0ea;
            --info: #2c6a88;
            --warning: #b6771e;
            --warning-soft: #fff4dc;
            --danger: #b74230;
            --danger-soft: #fff1ee;
            --success: #23744d;
            --success-soft: #e8f5ed;
            --shadow: 0 20px 45px rgba(23, 38, 31, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(44, 106, 136, 0.08), transparent 25%),
                linear-gradient(180deg, var(--page-alt) 0%, var(--page) 100%);
        }

        a {
            color: var(--accent);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
        }

        .sidebar {
            padding: 24px 20px;
            background: rgba(255, 255, 255, 0.8);
            border-right: 1px solid var(--line);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: auto;
        }

        .brand {
            display: grid;
            gap: 8px;
            margin-bottom: 24px;
        }

        .brand-kicker {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--info);
            font-weight: 700;
        }

        .brand-title {
            font-size: 22px;
            line-height: 1.1;
            font-weight: 800;
        }

        .brand-copy {
            margin: 0;
            font-size: 14px;
            color: var(--muted);
            line-height: 1.5;
        }

        .nav {
            display: grid;
            gap: 8px;
            margin-top: 20px;
        }

        .nav a {
            display: block;
            padding: 11px 14px;
            border-radius: 12px;
            border: 1px solid transparent;
            color: var(--ink);
            font-weight: 600;
        }

        .nav a.active {
            background: var(--accent-soft);
            border-color: rgba(34, 84, 61, 0.12);
            color: var(--accent-strong);
        }

        .sidebar-foot {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
            display: grid;
            gap: 12px;
        }

        .user-card {
            padding: 14px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .user-card strong {
            display: block;
            margin-bottom: 4px;
        }

        .user-card span {
            color: var(--muted);
            font-size: 13px;
        }

        .logout-form {
            margin: 0;
        }

        .logout-form button,
        .button,
        button,
        input[type="submit"] {
            border: 0;
            border-radius: 10px;
            padding: 11px 15px;
            background: var(--accent);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
        }

        .button-secondary,
        .button-muted,
        .table-actions .button-link,
        .inline-action button {
            background: #eef2ec;
            color: var(--ink);
        }

        .button-danger,
        .inline-action button.button-danger {
            background: var(--danger);
            color: #fff;
        }

        .button-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .content {
            padding: 28px;
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-head h1 {
            margin: 0 0 10px;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.08;
        }

        .page-head p {
            margin: 0;
            color: var(--muted);
            max-width: 760px;
            line-height: 1.55;
        }

        .page-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .grid {
            display: grid;
            gap: 20px;
        }

        .grid.two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .panel h2,
        .panel h3 {
            margin: 0 0 10px;
        }

        .panel-copy {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.55;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
        }

        .stat {
            padding: 16px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, #ffffff, #f8faf6);
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

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        th {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-success {
            background: var(--success-soft);
            color: var(--success);
        }

        .badge-warning {
            background: var(--warning-soft);
            color: #7a5618;
        }

        .badge-danger {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .badge-neutral {
            background: #edf1ef;
            color: #506157;
        }

        .empty {
            padding: 16px;
            border-radius: 14px;
            border: 1px dashed var(--line);
            background: #fafcf9;
            color: var(--muted);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 16px;
        }

        .alert-danger {
            background: var(--danger-soft);
            color: var(--danger);
            border: 1px solid rgba(183, 66, 48, 0.16);
        }

        .alert-success {
            background: var(--success-soft);
            color: var(--success);
            border: 1px solid rgba(35, 116, 77, 0.15);
        }

        form.stack,
        .stack {
            display: grid;
            gap: 16px;
        }

        .fields {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            font-weight: 700;
            font-size: 14px;
        }

        .field small {
            color: var(--muted);
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="datetime-local"],
        input[type="date"],
        input[type="password"],
        select,
        textarea,
        input[type="file"] {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
            color: var(--ink);
            background: #fff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 3px solid rgba(44, 106, 136, 0.14);
            border-color: var(--info);
        }

        .checkbox {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .checkbox input {
            width: auto;
            margin-top: 4px;
        }

        .field-error {
            color: var(--danger);
            font-size: 13px;
            font-weight: 700;
        }

        .actions-bar,
        .table-actions,
        .inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .inline-action {
            margin: 0;
        }

        dl.meta {
            margin: 0;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        dl.meta div {
            padding: 12px 14px;
            border-radius: 12px;
            background: #f8faf6;
            border: 1px solid var(--line);
        }

        dl.meta dt {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        dl.meta dd {
            margin: 0;
            font-weight: 600;
            line-height: 1.45;
        }

        pre.code {
            margin: 0;
            padding: 14px;
            border-radius: 14px;
            background: #12221c;
            color: #eef6f0;
            overflow: auto;
            font-size: 13px;
        }

        .split {
            display: grid;
            gap: 20px;
            grid-template-columns: 1.2fr 0.8fr;
        }

        .pill-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pill {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eef4f0;
            border: 1px solid var(--line);
            font-size: 13px;
            font-weight: 600;
        }

        @media (max-width: 1100px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                height: auto;
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }
        }

        @media (max-width: 820px) {
            .content {
                padding: 20px;
            }

            .page-head,
            .split,
            .grid.two,
            .grid.three,
            .fields,
            dl.meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/planner.css">
    <link rel="stylesheet" href="/css/schedule.css">
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-kicker">FMI University</div>
                <div class="brand-title">Schedule Management</div>
                <p class="brand-copy">Manage buildings, rooms, schedules, routes, and resources across FMI, Rectorate, and Block 2.</p>
            </div>

            <nav class="nav" aria-label="Primary navigation">
                <?php foreach ($navItems as $key => $item): ?>
                    <a href="<?= ui_escape($item['href']) ?>" class="<?= $active === $key ? 'active' : '' ?>">
                        <?= ui_escape($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-foot">
                <?php if ($user !== null): ?>
                    <div class="user-card">
                        <strong><?= ui_escape(trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?: ($user['username'] ?? 'Authenticated user')) ?></strong>
                        <span><?= ui_escape((string) ($user['email'] ?? $user['username'] ?? '')) ?></span>
                    </div>

                    <form class="logout-form" method="post" action="/logout">
                        <button type="submit">Log Out</button>
                    </form>
                <?php endif; ?>
            </div>
        </aside>

        <main class="content">
            <header class="page-head">
                <div>
                    <h1><?= ui_escape($title) ?></h1>
                    <?php if ($subtitle !== ''): ?>
                        <p><?= ui_escape($subtitle) ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($actions !== []): ?>
                    <div class="page-actions">
                        <?php ui_render_actions($actions); ?>
                    </div>
                <?php endif; ?>
            </header>
            <?php ui_alert(is_string($flashSuccess) ? $flashSuccess : null, 'success'); ?>
            <?php ui_alert(is_string($flashError) ? $flashError : null, 'danger'); ?>
    <?php
}

function ui_page_end(array $extraScripts = []): void
{
    global $__pageScripts;
    $scripts = array_merge($extraScripts, is_array($__pageScripts ?? null) ? $__pageScripts : []);
    ?>
        </main>
    </div>
    <script src="/js/api.js"></script>
    <script src="/js/main.js"></script>
    <script src="/js/validation.js"></script>
    <?php foreach ($scripts as $script): ?>
        <script src="/js/<?= ui_escape($script) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
    <?php
}

function ui_alert(?string $message, string $type = 'success'): void
{
    if ($message === null || $message === '') {
        return;
    }
    ?>
    <div class="alert alert-<?= ui_escape($type) ?>"><?= ui_escape($message) ?></div>
    <?php
}

function ui_render_actions(array $actions): void
{
    if ($actions === []) {
        return;
    }
    ?>
    <div class="table-actions">
        <?php foreach ($actions as $action): ?>
            <?php
            if (!is_array($action)) {
                continue;
            }

            $method = strtoupper((string) ($action['method'] ?? 'GET'));
            $label = (string) ($action['label'] ?? 'Open');
            $class = (string) ($action['class'] ?? '');
            ?>
            <?php if ($method === 'GET'): ?>
                <a class="button button-link <?= ui_escape($class) ?>" href="<?= ui_escape((string) ($action['href'] ?? '#')) ?>">
                    <?= ui_escape($label) ?>
                </a>
            <?php else: ?>
                <form class="inline-action" method="post" action="<?= ui_escape((string) ($action['href'] ?? '#')) ?>">
                    <?php if ($method !== 'POST'): ?>
                        <input type="hidden" name="_method" value="<?= ui_escape($method) ?>">
                    <?php endif; ?>
                    <?php foreach ((array) ($action['hidden'] ?? []) as $name => $value): ?>
                        <input type="hidden" name="<?= ui_escape((string) $name) ?>" value="<?= ui_escape((string) $value) ?>">
                    <?php endforeach; ?>
                    <button
                        type="submit"
                        class="<?= ui_escape($class) ?>"
                        <?php if (!empty($action['confirm'])): ?>
                            onclick="return confirm('<?= ui_escape((string) $action['confirm']) ?>');"
                        <?php endif; ?>
                    >
                        <?= ui_escape($label) ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}
