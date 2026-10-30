# Deployment and operations

The default [Compose file](../docker-compose.yml) is a development setup: public demo passwords, published MySQL, demo seeding on each app start, and no persistent app storage configuration. Use it for demonstrations. For a real installation, resolve the security and workflow defects in [KNOWN_ISSUES.md](KNOWN_ISSUES.md) before accepting real users or data. The examples here supply deployment configuration; they do not fix those application defects.

## Build a deployment image

The repository image targets Apache/PHP 8.2. Build it, then extend it to install the missing `mbstring` dependency and add deployment-specific Apache/PHP configuration.

```sh
docker build -t fmi-scheduler:base .
```

In a separate deployment directory, create `Dockerfile`:

```dockerfile
FROM fmi-scheduler:base
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mbstring \
    && rm -rf /var/lib/apt/lists/*
COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY php.ini /usr/local/etc/php/conf.d/production.ini
# Start Apache directly; the original entrypoint also imports demo data.
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["apache2-foreground"]
```

Use the Apache example below for `apache.conf`. Suggested `php.ini`:

```ini
display_errors=Off
log_errors=On
date.timezone=Europe/Sofia
session.save_path=/var/www/html/storage/sessions
session.use_strict_mode=1
session.cookie_secure=1
session.cookie_httponly=1
session.cookie_samesite=Lax
```

`session.cookie_secure=1` requires browser access over HTTPS. Configure a TLS reverse proxy; the container port in the example below is bound to loopback for that proxy.

```sh
docker build -t fmi-scheduler:deploy .
docker run --rm --entrypoint php fmi-scheduler:deploy -m
```

Verify `pdo_mysql`, `mbstring`, and `SimpleXML` appear. Pin and maintain image versions according to your deployment process.

## Apache access restrictions

The app requires the repository root as the document root. Its shipped `.htaccess` serves existing files directly and does not protect private files. A restricted Apache configuration should allow the front controller and assets while denying internal directories and configuration/data files.

For the Docker path `/var/www/html`, use this `apache.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    <DirectoryMatch "^/var/www/html/(app|config|database|docker|docs|storage)(/|$)">
        Require all denied
    </DirectoryMatch>
    <FilesMatch "(?i)^(\..*|.*\.(ini|sql|md|txt|ya?ml|sh)|Dockerfile)$">
        Require all denied
    </FilesMatch>
    <LocationMatch "^/(\..*|app|config|database|docker|docs|storage)(/|$)">
        Require all denied
    </LocationMatch>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

For native Apache/XAMPP, substitute the actual absolute project path and virtual host port. These rules deny HTTP access; they do not prevent PHP from including views or reading configuration. Controller-based export downloads still work. Keep writable storage non-executable.

Before release, verify `/.env`, `/.git/config`, `/config/app.ini`, `/database/seed.sql`, `/admin_credentials.txt`, and `/storage/logs/app.log` return 403/404, while `/login` and `/css/style.css` still work.

## Initialize a database without demo data

Provision an empty MySQL database with `utf8mb4`. Supply a database account allowed from the application host/container network. A bootstrap account needs CREATE privileges; the runtime account generally needs SELECT, INSERT, UPDATE, and DELETE on the application database. Use a separate administrative account for schema changes.

Keep a Docker-format environment file outside the checkout/build context, for example `/srv/fmi-scheduler-secrets/runtime.env`:

```text
DB_HOST=database-host
DB_PORT=3306
DB_NAME=schedule_management_system
DB_USER_NAME=scheduler
DB_USER_PASSWORD=replace-with-a-generated-password
LOG_LEVEL=GDPR
```

Replace all placeholders. Docker `--env-file` values are unquoted; quotes would become part of a password. Do not mount this file as PHP's INI-style `.env`. See [configuration](CONFIGURATION.md) for parser differences.

Import only `database/schema.sql` using a database client with bootstrap privileges. An example using the image's bundled client is:

```sh
docker run --rm --env-file /srv/fmi-scheduler-secrets/bootstrap.env --entrypoint bash fmi-scheduler:deploy -c 'MYSQL_PWD="$DB_USER_PASSWORD" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER_NAME" "$DB_NAME" < database/schema.sql'
```

`bootstrap.env` has the same key names but bootstrap credentials. If MySQL is on a Docker network, add `--network <network-name>` to commands that need it. A container's `127.0.0.1` is its own loopback, not the host or another container.

Create the first administrator without importing the demo seed:

1. Start the app with registration reachable only to the operator.
2. Register a new account with a strong password; registration creates a student.
3. In an administrative MySQL session, promote that exact account:

```sql
UPDATE users SET role = 'admin' WHERE username = 'your-operator-username';
```

4. Log out and in again so the session contains the new role.

There is no installer, password-reset email flow, or admin creation CLI. The old standalone user insert scripts are demonstration/legacy fixtures.

## Run and persist storage

Create a named volume and initialize its writable directories:

```sh
docker volume create fmi-scheduler-storage
docker run --rm --mount type=volume,source=fmi-scheduler-storage,target=/var/www/html/storage --entrypoint bash fmi-scheduler:deploy -c 'mkdir -p storage/logs storage/exports storage/sessions && chown -R www-data:www-data storage'
docker run -d --name fmi-scheduler --restart unless-stopped --env-file /srv/fmi-scheduler-secrets/runtime.env -p 127.0.0.1:8080:80 --mount type=volume,source=fmi-scheduler-storage,target=/var/www/html/storage fmi-scheduler:deploy
```

Add the database network if required. Configure the reverse proxy to forward the domain root to `127.0.0.1:8080`. Do not publish the database publicly. The current PDO code has no explicit MySQL TLS option; keep database traffic on a trusted network or add and test appropriate TLS support.

## Release verification

- Verify the extension list, database tables, and Apache access restrictions.
- Register and log in; verify each role using the [user guide](USER_GUIDE.md).
- Submit a request, check availability, approve it, and attach it to a schedule.
- Confirm writes fail for student accounts and the audit page is admin-only.
- Export a CSV and import a small disposable fixture; validate partial-error handling.
- Check writable storage, secure cookies over HTTPS, and server timezone.
- Review logs and JSON for the sensitive-data defects described in [known issues](KNOWN_ISSUES.md#logging-and-serialization).

No database-aware readiness endpoint is included. Apache being up alone does not prove database connectivity. Use an authenticated read as part of monitoring.

## Backups and restore

CSV/XML exports omit users and relationship tables and do not preserve imported IDs. Use MySQL backups for restoration.

For the local Compose database, this writes a dump inside the database container, then copies it to the host without shell output redirection:

```sh
docker compose exec db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump --no-tablespaces --single-transaction -u "$MYSQL_USER" "$MYSQL_DATABASE" > /tmp/fmi-scheduler-backup.sql'
docker compose cp db:/tmp/fmi-scheduler-backup.sql ./fmi-scheduler-backup.sql
```

For deployment, use equivalent commands through a backup account, store backups outside the repository with restricted access, and test restoration into a separate database. Back up persistent exports if needed; define log retention/rotation. File sessions can be discarded during maintenance if signing users out is acceptable.

To restore a dump, use an administrative client against the intended empty database:

```sql
USE schedule_management_system;
SOURCE /absolute/path/to/fmi-scheduler-backup.sql;
```

Never test a restore against the only live copy.

## Upgrades and rollback

Back up the database and storage, build a versioned image, review SQL differences, apply explicit schema changes, replace the application container, and repeat the smoke test. `schema.sql` uses `CREATE TABLE IF NOT EXISTS`; it is not a migration system and will not add columns to an existing table.

Keep the previous image and a compatible database backup. Rollback may require both the old image and database restoration after incompatible schema/data changes. The default seed is intended to avoid many duplicates through unique keys and existence checks, but rerunning it can reintroduce removed demo data; do not use the default seeding entrypoint for deployment.

## Existing GitLab pipeline

[.gitlab-ci.yml](../.gitlab-ci.yml) defines build and deploy stages for `main` and `dev`, using jobs tagged `HSS`. This is a runner-specific deployment path and is not automatically configured by cloning the GitHub repository.

Each job installs curl/bash, downloads the GitLab secure-files installer, and replaces the local Compose file with one of these external GitLab project Secure Files:

- `.secure_files/docker-compose.main.yaml` when the branch equals `CI_DEFAULT_BRANCH`.
- `.secure_files/docker-compose.dev.yaml` on `dev`.

The job rules explicitly allow only main/dev, so the default branch convention must match. The secure files themselves are not checked in; their services, credentials, volume mappings, and image naming cannot be verified from this checkout.

`build_app` runs a no-cache Compose build, expects the image name `w26-fmischeduler-yw72ij-app`, tags it with the branch name, and saves a tar artifact. `deploy_app` loads that artifact and recreates the app service after stopping/removing the prior one. The scripts use both `docker compose` and `docker-compose`; the runner needs both command forms or the pipeline must be adjusted consistently.

Prerequisites are a suitable HSS-tagged runner with Docker build/deploy access, access to the project's Secure Files, the expected Compose/image naming, network access for the installer, and configured persistent database/application storage. Review secure Compose files against the deployment requirements above. No test stage or application readiness verification is included, and replacement may cause downtime. Pushing main/dev on the configured GitLab project can deploy automatically.
