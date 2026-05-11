<?php $error = $flash['error'] ?? ''; ?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register | Schedule Manager</title>
<link rel="stylesheet" href="/css/style.css">
<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;}.reg-card{width:100%;max-width:420px;}</style>
</head>
<body>
<div class="card reg-card">
    <h1>Register</h1>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/register" class="stack">
        <div class="field"><label>Username</label><input type="text" name="username" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" required></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <button type="submit">Register</button>
        <p style="color:var(--muted);font-size:13px;text-align:center;">Already have an account? <a href="/login">Login</a></p>
    </form>
</div>
</body>
</html>
