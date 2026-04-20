<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'View.php';

$old = isset($old) && is_array($old) ? $old : [];
$error = isset($error) ? (string) $error : '';

$value = static function (string $key) use ($old): string {
    return View::escape($old[$key] ?? '');
};
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Система за управление на графици</title>
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
            width: min(1120px, calc(100% - 32px));
            min-height: 100vh;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
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
            max-width: 760px;
            margin: 0;
            font-size: 44px;
            line-height: 1.14;
        }

        .lead {
            max-width: 700px;
            margin: 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.65;
        }

        .capabilities {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            max-width: 720px;
        }

        .capability {
            min-height: 84px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.82);
            padding: 16px;
        }

        .capability strong {
            display: block;
            margin-bottom: 6px;
            font-size: 15px;
        }

        .capability span {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
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

        .auth-card p {
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
            font-weight: 700;
        }

        form {
            display: grid;
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

        .actions {
            display: grid;
            gap: 14px;
            margin-top: 4px;
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

        .demo-note {
            border-left: 4px solid var(--info);
            background: #eef8fb;
            border-radius: 6px;
            padding: 12px 14px;
            color: #244b5d;
            font-size: 14px;
            line-height: 1.45;
        }

        @media (max-width: 860px) {
            .auth-shell {
                grid-template-columns: 1fr;
                gap: 28px;
                padding: 32px 0;
            }

            h1 {
                font-size: 34px;
            }
        }

        @media (max-width: 560px) {
            .auth-shell {
                width: min(100% - 20px, 1120px);
            }

            .capabilities {
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
        <section class="intro" aria-labelledby="login-title">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true"><span></span></div>
                FMI Schedule Management
            </div>

            <div>
                <h1 id="login-title">Управление на графици, зали и маршрути в една система.</h1>
                <p class="lead">
                    Планиране на занятия и изпити, избор на подходящи физически или онлайн зали,
                    проверка за свободни ресурси и ориентиране между сгради, етажи и помещения.
                </p>
            </div>

            <div class="capabilities" aria-label="Основни възможности">
                <div class="capability">
                    <strong>Графици и изпити</strong>
                    <span>Създаване, преглед и разпределение на събития по зали.</span>
                </div>
                <div class="capability">
                    <strong>Сгради и етажи</strong>
                    <span>Работа с ФМИ, Ректорат, Втори блок и бъдещи локации.</span>
                </div>
                <div class="capability">
                    <strong>Маршрути</strong>
                    <span>Време за придвижване пеша, с автомобил или градски транспорт.</span>
                </div>
                <div class="capability">
                    <strong>Ограничения</strong>
                    <span>Капацитети, ресурси и правила за безопасно движение.</span>
                </div>
            </div>
        </section>

        <section class="auth-card" aria-labelledby="form-title">
            <h2 id="form-title">Вход</h2>
            <p>Влезте с имейл или потребителско име, за да продължите към таблото.</p>

            <?php if ($error !== ''): ?>
                <div class="alert" role="alert"><?= View::escape($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/login" novalidate>
                <label for="login">
                    <span>Имейл или потребителско име</span>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        value="<?= $value('login') ?>"
                        autocomplete="username"
                        required
                    >
                </label>

                <label for="password">
                    <span>Парола</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </label>

                <div class="actions">
                    <button type="submit">Вход в системата</button>
                    <p class="switch">Нямате акаунт? <a href="/app/views/auth/register.php">Регистрация</a></p>
                </div>
            </form>

            <p class="demo-note">
                Достъпът зависи от ролята на потребителя: администратор, график-мениджър,
                преподавател, студент или технически персонал.
            </p>
        </section>
    </main>
</body>
</html>
