<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'View.php';

$old = isset($old) && is_array($old) ? $old : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];

$value = static function (string $key) use ($old): string {
    return View::escape($old[$key] ?? '');
};

$fieldError = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? View::escape($errors[$key]) : '';
};
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | Система за управление на графици</title>
    <style>
        :root {
            --page: #f7faf8;
            --panel: #ffffff;
            --ink: #17201b;
            --muted: #5b6961;
            --line: #d8e1dc;
            --accent: #18755a;
            --accent-dark: #105740;
            --soft: #e8f5ef;
            --danger: #b42318;
            --danger-soft: #fff1f0;
            --info: #246b8f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--ink);
            background:
                linear-gradient(90deg, rgba(24, 117, 90, 0.07) 1px, transparent 1px),
                linear-gradient(0deg, rgba(36, 107, 143, 0.06) 1px, transparent 1px),
                var(--page);
            background-size: 44px 44px;
        }

        a {
            color: var(--accent-dark);
            font-weight: 700;
        }

        .auth-shell {
            width: min(1180px, calc(100% - 32px));
            min-height: 100vh;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 0.85fr 1.15fr;
            gap: 40px;
            align-items: center;
            padding: 48px 0;
        }

        .intro {
            display: grid;
            gap: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 700;
            color: var(--accent-dark);
            text-transform: uppercase;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border: 2px solid var(--accent);
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: var(--panel);
        }

        .brand-mark span {
            width: 22px;
            height: 22px;
            border: 2px solid var(--info);
            border-radius: 4px;
            display: block;
            position: relative;
        }

        .brand-mark span::before,
        .brand-mark span::after {
            content: "";
            position: absolute;
            background: var(--info);
        }

        .brand-mark span::before {
            width: 2px;
            height: 100%;
            left: 9px;
            top: 0;
        }

        .brand-mark span::after {
            width: 100%;
            height: 2px;
            left: 0;
            top: 9px;
        }

        h1 {
            margin: 0;
            font-size: 42px;
            line-height: 1.15;
        }

        .lead {
            margin: 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.65;
        }

        .role-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .role-list li {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.82);
            padding: 13px 14px;
            color: var(--muted);
            line-height: 1.45;
        }

        .role-list strong {
            color: var(--ink);
        }

        .auth-card {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            padding: 32px;
            box-shadow: 0 18px 45px rgba(23, 32, 27, 0.10);
        }

        .auth-card h2 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .auth-card > p {
            margin: 0 0 24px;
            color: var(--muted);
            line-height: 1.5;
        }

        .alert {
            border: 1px solid #f0b6ae;
            border-radius: 8px;
            background: var(--danger-soft);
            color: var(--danger);
            padding: 12px 14px;
            margin-bottom: 18px;
        }

        .alert strong {
            display: block;
            margin-bottom: 8px;
        }

        .alert ul {
            margin: 0;
            padding-left: 20px;
        }

        form {
            display: grid;
            gap: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        label {
            display: grid;
            gap: 7px;
            font-weight: 700;
        }

        label span {
            font-size: 14px;
        }

        input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 12px 13px;
            color: var(--ink);
            background: #ffffff;
            font: inherit;
        }

        input:focus {
            border-color: var(--accent);
            outline: 3px solid rgba(24, 117, 90, 0.18);
        }

        .optional {
            color: var(--muted);
            font-weight: 400;
        }

        .field-error {
            min-height: 18px;
            color: var(--danger);
            font-size: 13px;
            font-weight: 700;
        }

        .hint {
            border-left: 4px solid var(--info);
            background: #eef8fb;
            border-radius: 6px;
            padding: 12px 14px;
            color: #244b5d;
            font-size: 14px;
            line-height: 1.45;
        }

        .actions {
            display: grid;
            gap: 14px;
            margin-top: 2px;
        }

        button {
            border: 0;
            border-radius: 8px;
            padding: 13px 18px;
            background: var(--accent);
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
        }

        button:hover,
        button:focus {
            background: var(--accent-dark);
        }

        .switch {
            margin: 0;
            color: var(--muted);
            text-align: center;
        }

        @media (max-width: 920px) {
            .auth-shell {
                grid-template-columns: 1fr;
                gap: 28px;
                padding: 32px 0;
            }

            h1 {
                font-size: 34px;
            }
        }

        @media (max-width: 620px) {
            .auth-shell {
                width: min(100% - 20px, 1180px);
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .auth-card {
                padding: 22px;
            }

            h1 {
                font-size: 29px;
            }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="intro" aria-labelledby="register-title">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true"><span></span></div>
                FMI Schedule Management
            </div>

            <div>
                <h1 id="register-title">Създайте акаунт за достъп до графици, зали и маршрути.</h1>
                <p class="lead">
                    Новите регистрации се създават като студентски профили. След вход администратор
                    може да управлява роли, права и достъп според отговорностите в системата.
                </p>
            </div>

            <ul class="role-list" aria-label="Потребителски роли">
                <li><strong>Студенти:</strong> преглеждат графици, зали, етажи и маршрути.</li>
                <li><strong>Преподаватели:</strong> заявяват изпити, занятия и нужни ресурси.</li>
                <li><strong>Администрация:</strong> управлява графици, разпределения и import/export.</li>
                <li><strong>Технически екип:</strong> поддържа наличност на оборудване и зали.</li>
            </ul>
        </section>

        <section class="auth-card" aria-labelledby="form-title">
            <h2 id="form-title">Регистрация</h2>
            <p>Попълнете основните данни за профила. Полетата със звездичка са задължителни.</p>

            <?php if ($errors !== []): ?>
                <div class="alert" role="alert">
                    <strong>Моля, коригирайте следните полета:</strong>
                    <ul>
                        <?php foreach ($errors as $message): ?>
                            <li><?= View::escape($message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="/register" novalidate>
                <div class="form-grid">
                    <label for="first_name">
                        <span>Име *</span>
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?= $value('first_name') ?>"
                            autocomplete="given-name"
                            required
                        >
                        <span class="field-error"><?= $fieldError('first_name') ?></span>
                    </label>

                    <label for="last_name">
                        <span>Фамилия *</span>
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?= $value('last_name') ?>"
                            autocomplete="family-name"
                            required
                        >
                        <span class="field-error"><?= $fieldError('last_name') ?></span>
                    </label>
                </div>

                <div class="form-grid">
                    <label for="username">
                        <span>Потребителско име *</span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= $value('username') ?>"
                            autocomplete="username"
                            required
                        >
                        <span class="field-error"><?= $fieldError('username') ?></span>
                    </label>

                    <label for="email">
                        <span>Имейл *</span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= $value('email') ?>"
                            autocomplete="email"
                            required
                        >
                        <span class="field-error"><?= $fieldError('email') ?></span>
                    </label>
                </div>

                <div class="form-grid">
                    <label for="password">
                        <span>Парола *</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            required
                        >
                        <span class="field-error"><?= $fieldError('password') ?></span>
                    </label>

                    <label for="password_confirmation">
                        <span>Потвърждение на парола *</span>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                        >
                        <span class="field-error"><?= $fieldError('password_confirmation') ?></span>
                    </label>
                </div>

                <div class="form-grid">
                    <label for="faculty_number">
                        <span>Факултетен номер <span class="optional">(по желание)</span></span>
                        <input
                            type="text"
                            id="faculty_number"
                            name="faculty_number"
                            value="<?= $value('faculty_number') ?>"
                            autocomplete="off"
                        >
                        <span class="field-error"><?= $fieldError('faculty_number') ?></span>
                    </label>

                    <label for="phone">
                        <span>Телефон <span class="optional">(по желание)</span></span>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= $value('phone') ?>"
                            autocomplete="tel"
                        >
                        <span class="field-error"><?= $fieldError('phone') ?></span>
                    </label>
                </div>

                <label for="department">
                    <span>Катедра, специалност или отдел <span class="optional">(по желание)</span></span>
                    <input
                        type="text"
                        id="department"
                        name="department"
                        value="<?= $value('department') ?>"
                        autocomplete="organization"
                    >
                    <span class="field-error"><?= $fieldError('department') ?></span>
                </label>

                <p class="hint">
                    Профилът се активира веднага и използва студентската роля по подразбиране.
                    За служебни профили ролята се променя от администратор.
                </p>

                <div class="actions">
                    <button type="submit">Създаване на акаунт</button>
                    <p class="switch">Вече имате акаунт? <a href="/app/views/auth/login.php">Вход</a></p>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
