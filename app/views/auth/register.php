<?php $error = $flash['error'] ?? ''; ?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register | Schedule Manager</title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
<div class="card reg-card">
    <h1>Register</h1>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/register" class="stack">
        <div class="field"><label>Username</label><input type="text" name="username" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" required></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <button type="submit">Register</button>
        <p class="auth-switch">Already have an account? <a href="/login">Login</a></p>
    </form>
</div>
</body>
</html>
