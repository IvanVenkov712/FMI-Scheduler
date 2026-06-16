<?php
use App\Core\View;

function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function selected_attr($value, $current) {
    return (string) $value === (string) $current ? ' selected' : '';
}

function page_start($title, $active = 'dashboard') {
    $user = $GLOBALS['current_user'] ?? null;
    $flash = $GLOBALS['flash'] ?? [];
    $role = $GLOBALS['current_user_role'] ?? '';
    $nav = [
        'dashboard'  => 'Dashboard',
        'buildings'  => 'Buildings',
        'floors'     => 'Floors',
        'rooms'      => 'Rooms',
        'schedules'  => 'Schedules',
        'events'     => 'Events',
        'requests'   => 'Requests',
        'resources'  => 'Resources',
        'routes'     => 'Routes',
        'allocation/search' => 'Allocation',
        'import-export/import'     => 'Import',
        'import-export/export'     => 'Export',
        'users'      => 'Users',
    ];
    if ($role === 'admin') {
        $nav['audit-logs'] = 'Audit Log';
    }
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | Schedule Manager</title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="brand">FMI Schedule</div>
    <nav>
    <?php foreach ($nav as $key => $label): ?>
        <a href="/<?=$key?>" class="<?= $active === $key ? 'active' : '' ?>"> <?= $label ?> </a>
    <?php endforeach; ?>
    </nav>
    <?php if ($user): ?>
    <div class="user-box">
        <strong><?= e($user['username'] ?? $user['email'] ?? 'User') ?></strong>
        <span><?= e($role) ?></span>
        <form method="post" action="/logout">
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
    <?php endif; ?>
</aside>
<main>
    <h1><?= e($title) ?></h1>
    <?php if (!empty($flash['success'])): ?><div class="alert ok"><?= e($flash['success']) ?></div><?php endif; ?>
    <?php if (!empty($flash['error'])): ?><div class="alert err"><?= e($flash['error']) ?></div><?php endif; ?>
<?php }

function page_end() { ?>
</main>
</div>
<script src="/js/app.js"></script>
</body>
</html>
<?php }

function status_badge($s) {
    $s = strtolower((string)($s ?? ''));
    $c = match($s) {
        'active','approved','available' => 'ok',
        'pending','draft' => 'warn',
        'rejected','inactive','cancelled','unavailable' => 'err',
        default => 'neutral',
    };
    return '<span class="badge ' . $c . '">' . e($s) . '</span>';
}

function fmt_dt($v) {
    if (!$v) return '-';
    try { return (new DateTimeImmutable((string)$v))->format('d.m.Y H:i'); } catch (Throwable $e) { return (string)$v; }
}
