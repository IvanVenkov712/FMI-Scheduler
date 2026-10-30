# Known issues and specification gaps

These findings describe the current source, not fixed behavior. References identify where a maintainer can verify or address each issue. Production deployment should account for the exposure, authentication, and workflow defects below.

## File exposure and credentials

- **Private files are under the document root.** [docker/apache.conf](../docker/apache.conf) permits access to the repository root, and [.htaccess](../.htaccess) bypasses rewriting for existing files. Directory listings are disabled, but configuration, SQL, logs, exports, dotfiles, and credential notes are not explicitly denied. Apply [Apache restrictions](DEPLOYMENT.md#apache-access-restrictions) and verify denied URLs.
- **Demo credentials and seeding are enabled by default.** [Compose](../docker-compose.yml) uses public development passwords, publishes MySQL to host port 3307, and [entrypoint.sh](../docker/entrypoint.sh) runs the seed on every app start. Use schema-only initialization and a non-seeding entrypoint for deployment.
- **An earlier environment template contained remote database credentials.** The current [.env.example](../.env.example) uses local demo values. This replacement does not revoke previously committed credentials or erase Git history; rotate the old credentials if they remain valid.
- **Alternate secret filenames are not excluded.** Ignore/build rules name `.env` specifically. Keep production secret files outside the checkout/build context.

## Logging and serialization

- **Logger privacy filtering is disconnected.** [Logger::sanitize()](../app/logger/Logger.php) contains sensitive/GDPR masking, but `encode_context()` encodes the raw context. [Kernel](../app/core/Kernel.php) logs POST bodies. Consequently passwords and personal data can reach `storage/logs/app.log` even with `LOG_LEVEL=GDPR`. Wire sanitization into the writer and test both privacy modes; restrict and review existing logs.
- **JSON filtering does not traverse model objects.** [SensitiveDataHelper::strip()](../app/helpers/SensitiveDataHelper.php) walks arrays only, while controllers often put User/OnlineAccess objects into response arrays. [BaseController::json()](../app/controllers/BaseController.php) can therefore serialize nested `password_hash` or `access_key` fields. Convert models to safe arrays or explicitly traverse/redact objects before encoding, then verify user/event responses.
- **Redaction is not a complete data policy.** Audit details use array-based secret redaction, but audit columns retain actor/IP information. There is no configured retention policy, anonymization workflow, or automatic log rotation.

## Event workflows and authorization

- **Generic event CRUD bypasses request/approval services.** [EventController](../app/controllers/EventController.php) inherits `store/update/destroy` from [CrudController](../app/controllers/CrudController.php). Teachers, managers, and admins can submit fields such as `status=approved` through that path without booking/resource checks or owner checks. Prefer the request workflow operationally and consolidate writes behind the relevant services before deployment.
- **Self-approval and ownership checks are not consistently applied.** [UserEventAccessService](../app/services/UserEventAccessService.php) defines owner and non-self-approval checks, but the approval endpoint only checks role. Requestor IDs may be supplied by callers, and generic event writes are not scoped to the requestor.
- **Sessions cache role and active status.** [SessionHelper](../app/helpers/SessionHelper.php) stores identity/role at login; most guards do not reload them. Changing another user's role or disabling the account does not immediately invalidate an existing session. Add per-request account checks or explicit session revocation.
- **CSRF protection and login throttling are absent.** Registered form/JSON mutations rely on cookie sessions but have no CSRF token validation. Login/registration have no implemented rate limiting; registration checks nonempty passwords without the profile flow's eight-character minimum.
- **Authenticated data visibility is broad.** User lists/details, event/meeting details, and bulk exports are readable by all signed-in roles. Review access and field-level disclosure before real institutional use.

## Booking, transactions, and publication

- **Concurrent approvals can double-book.** [EventApprovalService](../app/services/EventApprovalService.php) checks availability then updates status without a shared room lock or database exclusion constraint. Competing requests can both observe the room as free.
- **Pending events do not reserve rooms.** This is the current [availability rule](../app/services/RoomAvailabilityService.php), but it can surprise users who interpret submission as a booking.
- **Publication may change status even when it fails.** [SchedulePublishingService](../app/services/SchedulePublishingService.php) approves a pending event before checking the schedule and its date range. A failure to attach can still leave an approved booking. Batch publication also permits partial success.
- **Transactions are connection-local.** [BaseService](../app/services/BaseService.php) wraps one repository's transaction. Separately constructed repositories use separate PDO connections, so composing operations across them is not automatically atomic. The wrapper also does not track ownership of an already-open transaction.
- **Resource checks are limited to room quantities.** There is no shared consumable inventory accounting, cross-room equipment reservation, teacher clash check, or student-group clash model.

## Routing and traffic optimization

- **Ordinary route search ignores timed constraints.** [RoutePlanningService](../app/services/RoutePlanningService.php) checks them only in accessible mode with both times supplied. Accessible mode excludes stairs but does not model every real accessibility condition.
- **There is no campus traffic simulation.** Allocation scores spare capacity, not movement cost; route search minimizes one distance field. The specification's walking/car/transit times, crowd aggregation, bottleneck optimization, and timetable-wide allocation are not implemented.
- **Movement-plan capacity checks are separate from path search.** They inspect a supplied route/group, defaulting to one person; they do not reserve capacity across independent journeys.
- **Scaling is unmeasured.** Several pages load whole tables; availability/allocation perform repeated lookups, and path search scans the queue linearly and queries outgoing edges as it proceeds. There is no pagination or performance test suite.

## Interface and model gaps

- **Some public actions have no route.** Request update, room resource sync, event reschedule/requirements/online provisioning, allocation candidates/reassign, exam hybrid preparation/validation, and constraint usability/overlap exist in controllers but are absent from [config/routes.php](../config/routes.php). A public PHP method is not an HTTP API.
- **Floor details has no browser template.** `GET /floors/{id}` uses generic `show()`, which expects nonexistent `app/views/floors/details.php`. JSON negotiation avoids rendering; use `/floors/{id}/plan` for the browser view until corrected.
- **Constraint listing reuses an incompatible template.** [MovementConstraintController](../app/controllers/MovementConstraintController.php) provides a `movement_constraints` collection to `routes/index`, whose primary UI expects route segments. JSON can expose the collection, but a dedicated browser constraint interface is missing.
- **Grid and ASCII plans diverge.** [RoomController::update_position()](../app/controllers/RoomController.php) writes coordinate strings to `position_code`; ASCII tokens may use labels. Dragging does not update `floors.ascii_plan`, and stored ASCII takes precedence on export. The grid is fixed at 12 by 5 and the server does not validate those coordinate bounds.
- **Schema geometry is not modeled.** The room schema includes `pos_x`, `pos_y`, `width`, `height`, `is_entrance`, `is_exit`, and `orientation`, but [Room](../app/models/Room.php) omits them. CRUD/model-based transfers do not expose them; graphical resizing/topology editing is incomplete.
- **Single-day schedules are rejected.** [ScheduleController](../app/controllers/ScheduleController.php) requires the end date to be strictly after the start date.
- **Calendar is an alias.** `/schedules/{id}/calendar` uses the schedule details view; no separate calendar engine is included.
- **Root-path hosting is assumed.** Templates use absolute URLs; subdirectory deployments are inconsistent.

## Imports, runtime, and maintenance

- **Transfers are not backups.** CSV/XML ignore input IDs and omit relationship/security tables. Foreign keys are not remapped; restoring an export can duplicate records or connect them incorrectly. Use MySQL dumps. See [format limitations](../IMPORT_EXPORT_FORMATS.md#limitations).
- **Imports can partly succeed and bypass workflows.** [ImportService](../app/services/ImportService.php) inserts models directly and collects row errors without a file-wide transaction. Imported events can bypass request/approval checks. There is no staged preview, general CSV BOM removal, or complete schema validation.
- **Import accepts server paths.** Authorized import requests may supply `file_path` to read paths accessible to PHP; this is not confined to an upload directory. Restrict imports to trusted operators or implement an upload-only interface.
- **The Docker image omits an explicit mbstring installation.** Generic CRUD calls `mb_strlen()`. Install/verify `mbstring`; the [deployment image example](DEPLOYMENT.md#build-a-deployment-image) includes it.
- **Startup can hide failed initialization.** The default entrypoint starts Apache after login/schema/seed failure, and there is no database-aware app readiness endpoint.
- **Schema application is not migration.** `CREATE TABLE IF NOT EXISTS` does not upgrade existing tables. Review and apply explicit SQL changes.
- **Timezones and cookies require server configuration.** Dates use `strtotime`/`date` and MySQL DATETIME without offset; the app sets no timezone or secure cookie options.
- **No automated regression suite or test jobs are checked in.** The [GitLab pipeline](../.gitlab-ci.yml) builds/deploys without a test stage and depends on external secure Compose files and an HSS-tagged runner. Syntax checks alone do not validate SQL, browser behavior, authorization, or deployment. Follow [CONTRIBUTING.md](../CONTRIBUTING.md) for current checks.
- **No license is declared.** A license decision is needed before representing the repository as openly licensed.
