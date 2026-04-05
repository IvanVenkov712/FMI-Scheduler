MyWebsite/
├── index.php           ← входна точка
├── bootstrap.php       ← autoloader, сесии, error handling
├── session.php         ← API за сесии (GET/POST/DELETE)
├── user.php            ← API за потребители
├── homepage.html        ← frontend
├── mystyle.css
├── myscript.js
├── classes/
│   ├── .htaccess       ← Deny from all
│   ├── Db.php          ← връзка с БД
│   ├── Session.php     ← управление на сесии
│   └── User.php        ← модел за потребител
└── php-templates/
    ├── head.php        ← преизползваем <head>
    └── profile.php     ← страница с шаблон
