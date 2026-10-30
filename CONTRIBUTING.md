# Development guide

Start with [setup](docs/SETUP.md), [architecture](docs/ARCHITECTURE.md), and the [component map](docs/COMPONENTS.md). There is no dependency installation, asset build, or automated test suite in this checkout. The [GitLab pipeline](.gitlab-ci.yml) builds and deploys on its configured runner without a test stage; pushing main/dev can trigger deployment.

## Working on a feature

Register HTTP actions in [config/routes.php](config/routes.php); a public controller method alone is not reachable. Keep role/session checks and HTTP responses in controllers, business invariants in services, prepared SQL in repositories, and presentation in PHP views.

Use constructor injection for related repositories/services when they must share a database connection. Models define repository/export fields, so schema and model changes must be reviewed together. Join tables with composite keys should be managed through their owning repositories, not generic CRUD.

New mutations should use explicit validated fields, appropriate role and ownership checks, and the workflow service instead of bypassing approval rules through generic CRUD. Do not copy the [documented security gaps](docs/KNOWN_ISSUES.md) into new endpoints. Escape template output with the shared `e()` or `View::escape()`.

## Syntax and configuration checks

PowerShell, from the repository root:

```powershell
$phpFiles = @(Get-ChildItem app, config -Recurse -Filter *.php) + @(Get-Item index.php)
$lintFailed = $false
foreach ($phpFile in $phpFiles) {
    php -l $phpFile.FullName
    if ($LASTEXITCODE -ne 0) { $lintFailed = $true }
}
if ($lintFailed) { throw 'PHP syntax checks failed.' }
docker compose config --quiet
git diff --check
```

With the Docker app running, its PHP version can be used instead:

```sh
docker compose exec app sh -c 'find app config -name "*.php" -exec php -l {} \; && php -l index.php'
```

Syntax checks do not exercise database statements or permissions. Verify PHP extensions with `php -m` in the runtime serving requests.

## Manual smoke test

Use a disposable local database and the demo accounts. Known failures should be compared against [KNOWN_ISSUES.md](docs/KNOWN_ISSUES.md); do not describe them as passing behavior.

1. Log in as each role; register a new student; log out and verify protected routes redirect or return JSON 401.
2. Create a building with a positive floor limit, create a floor and room, then verify duplicate codes and exceeding the floor limit are rejected.
3. Move a room on its floor grid and reload; verify the saved position. Inspect the building's four directional views.
4. Submit physical and online/hybrid requests with valid and invalid resource/meeting data. Verify the request starts pending.
5. Approve one physical request and verify an overlapping request cannot be approved through the approval service. Check that adjacent intervals are permitted.
6. Search eligible rooms and assign a room as a manager. Verify student/teacher accounts cannot call the assignment endpoint.
7. Publish an event within a schedule period, reject an outside-period attachment, and remove an attachment without deleting the event.
8. Create directed route edges and test reachable, unreachable, same-room, and stair-free route searches, including timed closures in accessible mode.
9. Export CSV/XML/text and import small disposable CSV/XML/ASCII fixtures. Check imported/error counts after partial failure.
10. Change profile credentials; verify admin role/status controls and admin-only audit access.
11. Test JSON requests with cookie persistence and `Accept: application/json`, not just browser forms.
12. Check server logs, browser console, secure-file restrictions, and the documented logging/serialization limitations.

Add focused regression tests when fixing substantive behavior, particularly authorization, booking concurrency, model serialization, transfer validation, and SQL changes. No test framework is currently installed.

## Database and container changes

`schema.sql` is initial schema, not a migration runner. For an existing database, provide reviewed upgrade SQL and backup/rollback instructions. Avoid requiring a volume deletion to make a feature work. Keep demo fixtures in `database/seed.sql`; deployment should initialize schema without those users.

The local image contains a source snapshot. Rebuild with `docker compose up --build -d app` after edits. Recreated app containers do not persist logs/exports/sessions without additional storage configuration.

## Documentation changes

Update the matching guide whenever behavior changes:

- Environment/config parser: [CONFIGURATION.md](docs/CONFIGURATION.md), [.env.example](.env.example).
- Routes, guards, fields, responses: [HTTP_API.md](docs/HTTP_API.md), [USER_GUIDE.md](docs/USER_GUIDE.md).
- Services/models/schema: [ARCHITECTURE.md](docs/ARCHITECTURE.md), [COMPONENTS.md](docs/COMPONENTS.md), [services.md](services.md).
- Transfer fields: [IMPORT_EXPORT_FORMATS.md](IMPORT_EXPORT_FORMATS.md).
- Runtime or operations: [SETUP.md](docs/SETUP.md), [DEPLOYMENT.md](docs/DEPLOYMENT.md).
- Fixed limitations: remove or revise the corresponding [known issue](docs/KNOWN_ISSUES.md) after verification.

Keep the original Bulgarian assignment/specification as planning references. Do not claim every requirement is implemented. Keep real credentials, session cookies, database dumps, and generated exports/logs out of commits and image build contexts.
