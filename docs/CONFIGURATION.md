# Configuration and environment variables

## Precedence

[Kernel](../app/core/Kernel.php) loads `config/app.ini`, then the root `.env`, using PHP's `parse_ini_file(..., INI_SCANNER_TYPED)`. [Config::get()](../app/core/Config.php) resolves each key in this order:

1. Process environment (`getenv`), including empty strings.
2. Root `.env`.
3. `config/app.ini`.
4. The default passed by the caller.

`.env` is ignored by Git and excluded from the Docker build. The default Compose file sets the app's variables to fixed development values; it has no `env_file:` or variable interpolation for these settings. Editing a host `.env` does not change those container values. Edit or override Compose's `environment:` configuration when necessary.

## Application settings

Defaults below are the checked-in `config/app.ini` fallbacks, unless noted.

| Variable | Default | Purpose |
| --- | --- | --- |
| `DB_HOST` | `127.0.0.1` | MySQL host in the PDO connection. Use `db` inside Compose and `127.0.0.1` for a database exposed on the host. |
| `DB_PORT` | `3306` | MySQL TCP port. The native-PHP example uses `3307` to reach the local Compose database. |
| `DB_NAME` | `schedule_management_system` | Existing database to connect to and, in Docker's default entrypoint, initialize. The PHP code does not create the database. |
| `DB_USER_NAME` | `root` | Database login. Compose uses the development account `app`; use a dedicated account for deployment. |
| `DB_USER_PASSWORD` | Empty string | Password for that login. Compose uses the public local value `app_password`. |
| `DB_USER` | No INI value | Legacy fallback alias used by `Database` only if `DB_USER_NAME` cannot be resolved. Because the INI already defines the primary key, setting this alias alone normally has no effect. |
| `DB_PASS` | No INI value | Legacy fallback alias used only if `DB_USER_PASSWORD` cannot be resolved; likewise shadowed by the INI primary key. |
| `LOG_LEVEL` | `GDPR` | Intended privacy mode: `Normal` or `GDPR` (case-insensitive). It is not a severity threshold. The current writer bypasses redaction, so it does not currently protect log output; see [known issues](KNOWN_ISSUES.md#logging-and-serialization). |
| `WS_ADDRESS` | `127.0.0.1` | Reserved WebSocket address. No current PHP or JavaScript component consumes it and no WebSocket server is included. Compose sets it to `localhost`. |
| `WS_PORT` | `2229` | Reserved WebSocket port, currently unused. No corresponding service or published port is needed. |

Use the primary `DB_USER_NAME`/`DB_USER_PASSWORD` names. The Docker bootstrap recognizes those names, not the aliases. Its shell code requires nonempty `DB_HOST`, `DB_NAME`, and `DB_USER_NAME` to import SQL, defaults `DB_PORT` to 3306, and permits an empty password. It does not read `config/app.ini`.

## MySQL container settings

These variables belong to the `db` service/image, not the PHP application.

| Variable | Local Compose value | Purpose |
| --- | --- | --- |
| `MYSQL_DATABASE` | `schedule_management_system` | Database created when the MySQL data directory is initialized. |
| `MYSQL_USER` | `app` | Non-root account created for that initial database. |
| `MYSQL_PASSWORD` | `app_password` | Initial password of that account. Must match the app's `DB_USER_PASSWORD`. |
| `MYSQL_ROOT_PASSWORD` | `root_password` | Initial root password; also embedded in the local health check. |

Changing these values does not change credentials in an already initialized `db_data` volume. Update existing accounts with SQL, or intentionally initialize a separate empty volume. Changing the root password also requires updating the health check. PHP `DB_*` variables alone do not configure the MySQL image.

## File syntax and passwords

The PHP `.env` reader is an INI parser, not a general dotenv implementation. Use simple `KEY=value` lines and quote values containing punctuation. The example is deliberately comment-free: PHP INI uses semicolon comments, while Docker's environment-file readers use hash comments. Hash-prefixed prose can cause PHP INI parsing errors. See the [PHP INI documentation](https://www.php.net/manual/en/function.parse-ini-file.php).

```ini
DB_HOST=127.0.0.1
DB_PORT=3307
DB_NAME=schedule_management_system
DB_USER_NAME=app
DB_USER_PASSWORD='local-value-with-$-and-#'
LOG_LEVEL=GDPR
```

PHP INI accepts single-quoted literal strings and removes their surrounding quotes, as implemented in the [PHP 8.2 INI scanner](https://github.com/php/php-src/blob/PHP-8.2/Zend/zend_ini_scanner.l#L383). Double-quoted values can expand expressions such as `${NAME}`. Single quoting is useful for literal dollar signs; use process environment variables for complex secrets that cannot be represented consistently.

Docker Compose interprets its own project `.env` syntax separately, and may interpolate dollar signs in double-quoted values. Single quoting prevents that interpolation and also works for simple literal PHP INI values. In a Compose YAML `environment:` value, escape a literal dollar sign as `$$`. Avoid sharing one complex secret file between PHP's INI parser, Compose interpolation, and `docker run --env-file`; the latter treats quote characters as part of the value.

The Docker entrypoint has a third, simple parser for a mounted app `.env`: it accepts `KEY=value`, optional `export`, and removes surrounding single or double quotes. It performs no shell evaluation. It fills variables that are unset **or empty**; this differs from PHP `Config::get()`, where an explicitly empty process value still wins.

## Paths and runtime settings

These are configured in code or PHP/Apache configuration, not through app environment variables:

| Setting | Current behavior |
| --- | --- |
| Logs | `storage/logs/app.log`; failures fall back to PHP's error log. |
| Exports | `storage/exports`; services may override the directory through constructor injection. |
| Sessions | PHP file sessions, falling back to `storage/sessions` if the configured directory is unusable. |
| Timezone | The app sets no timezone. Configure PHP `date.timezone` and align the database/operational convention. Stored dates have no timezone offset. |
| Session cookies | PHP defaults unless changed in `php.ini`; configure Secure, HttpOnly, and SameSite for deployment. |
| Upload limits | PHP `upload_max_filesize`/`post_max_size`; no application environment setting. |
| Debug | Kernel disables browser error display and enables `E_ALL`; no `APP_ENV` or `APP_DEBUG` setting exists. |
| HTTP ports | Compose publishes app `8080:80` and MySQL `3307:3306`; these are port mappings, not app variables. |

Keep production secrets outside the checkout and Docker build context. The existing ignore rules name `.env` specifically; alternate names such as `.env.production` are not automatically excluded.

## GitLab pipeline variables

These are used or declared in [.gitlab-ci.yml](../.gitlab-ci.yml), not read by PHP:

| Variable | Purpose |
| --- | --- |
| `CI_COMMIT_BRANCH` | GitLab-provided branch name; selects main/dev jobs, chooses the secure Compose file, and tags the saved Docker image. |
| `CI_DEFAULT_BRANCH` | GitLab-provided default branch name; the setup script selects the main Compose file when the current branch matches it. The job rules separately permit only `main`/`dev`, so keep that convention aligned. |
| `DOCKER_DRIVER` | Set to `overlay2` for the pipeline's Docker environment. It does not configure PHP or the app database. |
| `CI_CONFIG_HASH` | Fixed value declared in the YAML but not referenced by checked-in job scripts; any external runner use is outside this checkout. |

The downloaded secure Compose files can define additional deployment settings that are not available in this repository. Their names and pipeline prerequisites are described in [deployment](DEPLOYMENT.md#existing-gitlab-pipeline).
