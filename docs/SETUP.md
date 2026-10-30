# Installation and local runs

## Requirements

Use the PHP 8.2 runtime targeted by [Dockerfile](../Dockerfile), MySQL 8.0 as in [docker-compose.yml](../docker-compose.yml), and a modern browser. The source uses PHP 8 features, but other PHP/MySQL versions do not have a compatibility test matrix.

Native PHP needs PDO, `pdo_mysql`, `mbstring`, SimpleXML/libxml, session, JSON, and standard date/filter functionality. The image explicitly installs PDO/MySQL and mysqli; it does **not** install `mbstring`, which generic CRUD validation calls. See [known issues](KNOWN_ISSUES.md) and the image extension example in [deployment](DEPLOYMENT.md). mysqli is installed by Docker but database access in the app uses PDO.

No `composer install`, `npm install`, package manager, or asset compilation is needed.

## Docker

From the repository root:

```sh
docker compose up --build -d
docker compose logs -f app
```

Visit `http://localhost:8080`. The database is accessible to host tools at `127.0.0.1:3307`, with database `schedule_management_system`, user `app`, and local password `app_password`. Within the Compose network, the app uses `db:3306`.

The app entrypoint:

1. Loads a mounted `/var/www/html/.env`, if present, for missing or empty environment values.
2. Checks database login, retrying connection failures up to 30 times with two-second pauses. Authentication failures skip the import immediately.
3. Runs `database/schema.sql`, followed by `database/seed.sql`.
4. Starts Apache even if database login or SQL import failed.

The seed provides campus locations, rooms, equipment, route segments, sample events, schedules, movement constraints, and demo users. Several events are dated June 2026; date filters for another period will not show those events. See the [README](../README.md#demo-accounts) for logins.

The `db_data` named volume persists database contents. Logs, exports, and sessions have no explicit persistent app volume in the local Compose file. Recreating the app container can lose them. Dockerfile copies source at build time; after changes run:

```sh
docker compose up --build -d app
```

Check extension availability:

```sh
docker compose exec app php -m
```

To stop and remove the containers without deleting database data:

```sh
docker compose down
```

Avoid `docker compose down --volumes` unless you intend to discard the local database.

## Native Apache/PHP or XAMPP

1. Clone or copy the repository to a local directory.
2. Enable the required PHP extensions in the PHP configuration used by Apache. CLI and Apache may use different `php.ini` files.
3. Copy the example configuration and edit it for your database.

PowerShell:

```powershell
Copy-Item .env.example .env
```

POSIX shell:

```sh
cp .env.example .env
```

The example targets MySQL exposed by Docker at `127.0.0.1:3307`. For native MySQL use its actual port, usually `3306`, and an existing database user. See [configuration](CONFIGURATION.md).

4. Create an empty database and grant your application user access. For example, in an administrative MySQL session, substituting a password:

```sql
CREATE DATABASE schedule_management_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'app'@'localhost' IDENTIFIED BY 'replace-with-your-password';
GRANT ALL PRIVILEGES ON schedule_management_system.* TO 'app'@'localhost';
```

The MySQL account host must match the connection origin. These example grants are for a local native setup.

5. Import the schema using a MySQL client. In its interactive session, use paths appropriate to your checkout:

```sql
USE schedule_management_system;
SOURCE C:/path/to/WebProject/database/schema.sql;
SOURCE C:/path/to/WebProject/database/seed.sql;
```

Omit the second `SOURCE` for an empty installation. Native PHP does not initialize or seed the database automatically. The older `*Insert.sql` files are optional legacy user scripts, not required setup steps.

6. Enable Apache `mod_rewrite` and allow `.htaccess`. Use a virtual host whose document root is the **repository root**, containing `index.php`:

```apache
Listen 8080
<VirtualHost *:8080>
    ServerName localhost
    DocumentRoot "C:/path/to/WebProject"
    <Directory "C:/path/to/WebProject">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Use a free port and avoid adding a duplicate `Listen` directive. Include the private-file restrictions from [deployment](DEPLOYMENT.md#apache-access-restrictions), even on a shared development machine. Restart Apache and visit `http://localhost:8080`.

Hosting at the domain root is recommended: templates contain absolute paths such as `/login` and `/css/style.css`. A URL under `/WebProject/` is not consistently supported. The `public/` directory contains assets and has no front controller; making it the document root will not run the app. PHP's built-in server does not process the supplied Apache rewrite rules.

7. Ensure the Apache account can write `storage/logs`, `storage/exports`, and, when needed, `storage/sessions`. The session helper uses PHP's configured session path if writable, otherwise creates `storage/sessions`.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| Docker cannot connect to its engine | Start Docker Desktop/the Docker daemon and verify `docker version`. |
| Port 8080 or 3307 already in use | Change the host side of the corresponding Compose port mapping. Update native `DB_PORT` if changing the database mapping. |
| `Access denied` from MySQL | Check the configured user, password, account host grants, and quoted values; see [configuration](CONFIGURATION.md). Compose settings override a mounted `.env`. |
| Tables or columns are missing | Check startup logs. `CREATE TABLE IF NOT EXISTS` does not upgrade existing tables; restore/import the correct schema into a separate database or apply reviewed SQL changes. |
| Root works but other pages return Apache 404 | Enable `mod_rewrite`, `AllowOverride All`, and the root `.htaccess`. |
| Styles or navigation fail under a subdirectory | Use a root-mounted virtual host. |
| `Call to undefined function ...mb_strlen()` | Install/enable `mbstring` in the PHP runtime handling the request. |
| XML import fails | Verify SimpleXML is enabled and XML is well-formed. |
| A new floor cannot be added | Increase the building's `floor_count` before inserting more floors; zero allows no floors. |
| Registration succeeds but login is still needed | Browser registration redirects to login. JSON registration logs the new user in. |
| Schedule creation rejects one day | Current validation requires `end_date` after `start_date`, so equal dates fail. |
| A room drag fails | Check permissions and duplicate `position_code` values; grid moves use coordinates rather than room labels. |
| Generic server error | Read `storage/logs/app.log` and Apache error logs. Treat existing logs as sensitive due to the [logging defect](KNOWN_ISSUES.md#logging-and-serialization). |
