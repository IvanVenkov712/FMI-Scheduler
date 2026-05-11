<?php $error = $flash['error'] ?? ''; ?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login | Schedule Manager</title>
<link rel="stylesheet" href="/css/style.css">
<style>
    body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .login-card { width: 100%; max-width: 400px; }
    .login-card h1 { margin-bottom: 6px; }
    .login-card p { color: var(--muted); margin-bottom: 20px; }
</style>
</head>
<body>
<div class="card login-card">
    <h1>FMI Schedule</h1>
    <p>Sign in to manage buildings, rooms, schedules, and routes.</p>

    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="/login" class="stack">
        <div class="field">
            <label for="identity">Email or Username</label>
            <input type="text" id="identity" name="identity" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
