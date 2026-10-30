# Component reference

This is a source map of the current application. Public controller methods become HTTP endpoints only when listed in [config/routes.php](../config/routes.php). See [HTTP_API.md](HTTP_API.md) for the registered surface and [services.md](../services.md) for service contracts.

## Core

| File/class | Responsibility |
| --- | --- |
| [index.php](../index.php) | Defines `ROOT`, registers the autoloader, boots and runs the kernel. |
| [Autoloader](../app/core/Autoloader.php) | Loads `App` classes from `app/`, resolving directory/file case differences when necessary. |
| [Config](../app/core/Config.php) | Merges INI files and resolves process environment overrides. |
| [Kernel](../app/core/Kernel.php) | Starts the session, registers errors, matches routes, dispatches actions, renders results, logs requests. |
| [Request](../app/core/Request.php) | Captures request metadata, normalizes paths, supports form `_method`, detects JSON clients, builds log context. |
| [Router](../app/core/Router.php) | Matches ordered `METHOD /path/{parameter}` definitions with named regex captures. |
| [Controller](../app/core/Controller.php) | Shared rendering, redirects, GET/POST input, JSON body parsing, required-field checks, sensitive-data helper calls. |
| [View](../app/core/View.php) | Resolves PHP templates, extracts data, buffers output, supports optional layouts, exposes shared UI globals, escapes text. |
| [Database](../app/core/Database.php) | Opens a `utf8mb4` PDO/MySQL connection with exception mode and associative fetches. |
| [Model](../app/core/Model.php) | Fills declared properties and serializes public object data using `to_array()`. |
| [Repository](../app/core/Repository.php) | Prepared CRUD, model mapping, SQL helpers, filters, and connection-local transactions. Requires a model with `id`. |
| [ErrorHandler](../app/core/ErrorHandler.php) | Handles exceptions, warnings, fatal-error logging, and safe HTML/JSON error responses. |

## Controllers

| File/class | Responsibilities and collaborators |
| --- | --- |
| [BaseController](../app/controllers/BaseController.php) | Session/role guards, HTML/JSON responses, flash messages, safe return redirects, ID/payload helpers, audit integration. |
| [CrudController](../app/controllers/CrudController.php) | Reusable index/show/create/store/edit/update/delete actions; field-rule validation, filter hooks, database error translation. Default writers: admin and schedule manager. |
| [AuthController](../app/controllers/AuthController.php) | Login, registration, logout, current-session identity; uses login/register services. |
| [DashboardController](../app/controllers/DashboardController.php) | Authenticated counts and event overview from building/room/event/schedule repositories. |
| [BuildingController](../app/controllers/BuildingController.php) | Building CRUD, floor listing, vertical views with rooms grouped by floor. |
| [FloorController](../app/controllers/FloorController.php) | Floor CRUD, room listing, grid plan, ASCII import/export through transfer services. |
| [RoomController](../app/controllers/RoomController.php) | Room CRUD, availability/resources/events, grid coordinate persistence; resource synchronization exists as an unregistered action. |
| [ResourceController](../app/controllers/ResourceController.php) | Catalog listing and insertion through the generic CRUD base. |
| [EventController](../app/controllers/EventController.php) | Generic event CRUD, details/print view, request submission, approval/rejection. Other service actions exist without routes. |
| [RequestController](../app/controllers/RequestController.php) | Manageable/approvable request lists, submission, review; uses access/request services. Pending-request update is currently unregistered. |
| [ExamSessionController](../app/controllers/ExamSessionController.php) | Exam creation form and exam setup service; hybrid preparation and validation actions are currently unregistered. |
| [ScheduleController](../app/controllers/ScheduleController.php) | Schedule CRUD, details/calendar alias, print view, publishing/removing event associations. |
| [AllocationController](../app/controllers/AllocationController.php) | Eligible-room search and best-room assignment; candidate listing/reassignment are currently unregistered. |
| [RouteController](../app/controllers/RouteController.php) | Directed segment list/create/details, map alias, route search, print report; accessible search is selected with a request flag. |
| [MovementConstraintController](../app/controllers/MovementConstraintController.php) | Constraint list/insertion; segment usability/overlap query actions are currently unregistered. Reuses route views. |
| [ImportExportController](../app/controllers/ImportExportController.php) | Transfer forms, uploaded files/XML text/server paths, import result handling, CSV/XML/text downloads. |
| [UserController](../app/controllers/UserController.php) | User listing/details, own profile email/password updates, admin role/status changes; excludes self role/status changes. |
| [AuditLogController](../app/controllers/AuditLogController.php) | Admin-only recent audit records filtered by action, username, and date. |

## Services

Every service is covered in the [service reference](../services.md): `BaseService` (including its internal `ServiceAbort` class), `DataTransferService`, `AuditLogService`, `AuthorizationService`, `LoginService`, `RegisterService`, `UserEventAccessService`, `EventRequestService`, `EventApprovalService`, `ExamSessionSetupService`, `OnlineAccessService`, `RoomAvailabilityService`, `RoomAllocationService`, `RoomResourceService`, `SchedulePublishingService`, `RoutePlanningService`, `RouteSegmentService`, `MovementConstraintService`, `ImportService`, and `ExportService`.

## Repositories

All inherit the shared CRUD/transaction helpers. Domain-specific methods return models, model arrays, scalar values, or booleans as indicated by their source.

| File/class | Table and additional queries |
| --- | --- |
| [UserRepository](../app/repositories/UserRepository.php) | `users`: username/email/identity lookup, requested events, role lookup, filtered users, profile credentials and role/status updates. |
| [BuildingRepository](../app/repositories/BuildingRepository.php) | `buildings`: filters, ordered floors, floors and rooms grouped for vertical views. |
| [FloorRepository](../app/repositories/FloorRepository.php) | `floors`: filters, building/room lookup; insert locks the building row and enforces its floor limit within a transaction. |
| [RoomRepository](../app/repositories/RoomRepository.php) | `rooms`: filters, floor/events, room resource quantities and updates, availability, incoming/outgoing route edges. |
| [ResourceRepository](../app/repositories/ResourceRepository.php) | `resources`: catalog search. |
| [EventRepository](../app/repositories/EventRepository.php) | `events`: joined filters, interval queries, requestor/room/online details, resource requirement replacement, online access upsert/detach. |
| [OnlineAccessRepository](../app/repositories/OnlineAccessRepository.php) | `online_access`: base CRUD for meeting records. |
| [ScheduleRepository](../app/repositories/ScheduleRepository.php) | `schedules`: filters, schedule events, publishable candidates, attach/detach/existence checks. |
| [RouteSegmentRepository](../app/repositories/RouteSegmentRepository.php) | `route_segments`: route filters and endpoint room lookups. |
| [MovementConstraintRepository](../app/repositories/MovementConstraintRepository.php) | `movement_constraints`: constraints overlapping a segment/time interval. |
| [AuditLogRepository](../app/repositories/AuditLogRepository.php) | `audit_logs`: recent records with filters; default limit 200. |

Composite-key associations are handled through owning repositories rather than the generic `Repository`, which assumes an `id` column.

## Models

Models are plain mutable records. Field defaults are in the source; database constraints are in [schema.sql](../database/schema.sql). Model fields also determine CSV/XML export columns.

| Model | Data represented |
| --- | --- |
| [User](../app/models/User.php) | Identity, names, email, password hash, role, active flag. |
| [Building](../app/models/Building.php) | Name/code, address, campus area, floor limit, active flag. |
| [Floor](../app/models/Floor.php) | Building ID, level/name, stored ASCII plan, notes. |
| [Room](../app/models/Room.php) | Floor ID, name, position code, type/size, capacity, active flag. Extra schema geometry fields are not modeled. |
| [Resource](../app/models/Resource.php) | Resource name and description. |
| [RoomResource](../app/models/RoomResource.php) | Resource/room IDs, joined resource name, available quantity. |
| [Event](../app/models/Event.php) | Requestor/room/online IDs, type/name/subject, interval, delivery mode, student count, status; `is_pending()`, `is_approved()`, `is_rejected()`. |
| [OnlineAccess](../app/models/OnlineAccess.php) | Platform, meeting link, access key, host notes. |
| [ResourceRequirement](../app/models/ResourceRequirement.php) | Event/resource IDs, joined resource name, required quantity, notes. |
| [Schedule](../app/models/Schedule.php) | Name, description, inclusive date period. |
| [ScheduleEvent](../app/models/ScheduleEvent.php) | Schedule/event join pair. |
| [RouteSegment](../app/models/RouteSegment.php) | Directed endpoint IDs, distance, corridor capacity, stairs/elevator/available flags. |
| [MovementConstraint](../app/models/MovementConstraint.php) | Segment ID, rule type, capacity limit, interval, notes. |
| [AuditLog](../app/models/AuditLog.php) | Actor, action/request/entity metadata, IP, details JSON, creation time. |

## Helpers, logging, and exceptions

| File/class | Contract |
| --- | --- |
| [StringHelper](../app/helpers/StringHelper.php) | Normalizes string comparisons through trimming/lowercasing. |
| [TimeHelper](../app/helpers/TimeHelper.php) | Parses intervals with `strtotime`; validates start before end and checks strict overlap. |
| [EventDeliveryModeHelper](../app/helpers/EventDeliveryModeHelper.php) | Normalizes and validates physical/online/hybrid modes, online-data requirements, physical-room compatibility. |
| [OnlineAccessHelper](../app/helpers/OnlineAccessHelper.php) | Builds meeting models from arrays/scalars, checks platform/URL, generates random access keys. |
| [ResourceRequirementsHelper](../app/helpers/ResourceRequirementsHelper.php) | Converts array/object requirements to normalized objects; rejects invalid quantities, drops zero-quantity entries, returns `null` for invalid input. |
| [SessionHelper](../app/helpers/SessionHelper.php) | Starts file sessions with fallback directory, stores identity/role snapshots, regenerates IDs, handles flash messages and logout. |
| [SensitiveDataHelper](../app/helpers/SensitiveDataHelper.php) | Removes or redacts matching secret keys recursively in arrays. Does not traverse model objects. |
| [Logger](../app/logger/Logger.php) | Timestamped file logging and error-log fallback, severity methods, exception context. Contains unused sensitive/GDPR redaction helpers; see [known issues](KNOWN_ISSUES.md#logging-and-serialization). |
| [InsertException](../app/exceptions/InsertException.php) | Domain exception for insert failures, preserving the underlying cause; also used for the floor-limit validation error. |

## Views and browser components

Every template directory below is under [app/views](../app/views). There is no single-page application.

| Directory/files | Purpose |
| --- | --- |
| `shared/ui.php` | Global escaping/selection/role helpers, page header/navigation/footer, flash messages, badges, date formatting. |
| `auth/login.php`, `auth/register.php` | Authentication forms. |
| `dashboard/index.php` | Counts and event overview. |
| `buildings/index.php`, `create.php`, `edit.php`, `details.php`, `vertical.php` | Building maintenance and directional cross-section. |
| `floors/index.php`, `create.php`, `edit.php`, `plan.php` | Floor maintenance and inline JavaScript drag/drop grid. There is no `floors/details.php`, despite the generic show route. |
| `rooms/index.php`, `create.php`, `edit.php`, `details.php`, `availability.php` | Room maintenance/details and availability presentation. |
| `events/index.php`, `create.php`, `edit.php`, `details.php`, `export.php` | Activity maintenance, online details, print report. |
| `requests/index.php`, `create.php`, `review.php` | Request lists, submission, approval review. |
| `schedules/index.php`, `create.php`, `details.php`, `export.php` | Dated collections, event membership, print report. |
| `resources/index.php` | Resource catalog and creation form. |
| `routes/index.php`, `details.php`, `export.php` | Segment list/search/forms, details, printable path. Also reused for constraints. |
| `allocation/search.php` | Eligible-room search and results. |
| `import-export/import.php`, `export.php` | CSV/XML/ASCII inputs and export selection. |
| `users/index.php`, `details.php`, `profile.php` | User browsing/admin role/status controls and own profile credentials. |
| `audit-logs/index.php` | Audit filter form and results. |
| `errors/403.php`, `404.php`, `500.php` | Error pages. |
| [public/css/style.css](../public/css/style.css) | Shared layout, forms, tables, badges, grid/building visuals, responsive and print styling. |
| [public/js/app.js](../public/js/app.js) | Alerts, delete confirmation, browser validation, form drafts in `sessionStorage`, JSON `api` fetch wrapper. Password/file/token/secret/access-key fields are excluded from drafts. |

## Configuration, database, and tooling

| File/directory | Purpose |
| --- | --- |
| [config/routes.php](../config/routes.php) | Authoritative ordered HTTP route table. |
| [config/app.ini](../config/app.ini), [.env.example](../.env.example) | Non-secret defaults and local environment template. |
| [.htaccess](../.htaccess) | Apache rewrite of asset aliases and application routes; disables directory listings. |
| [Dockerfile](../Dockerfile) | Apache/PHP image, MySQL client/extensions, source copy, storage permissions. |
| [docker-compose.yml](../docker-compose.yml) | Local app/MySQL services, ports, database volume, health-based startup dependency. |
| [.gitlab-ci.yml](../.gitlab-ci.yml) | GitLab main/dev build and deployment jobs, external secure Compose files, HSS runner requirement; no test stage. |
| [docker/entrypoint.sh](../docker/entrypoint.sh) | Simple environment-file parsing, database wait/login, schema/seed import, Apache exec. |
| [docker/apache.conf](../docker/apache.conf) | Local virtual host; needs restrictions for deployment. |
| [database/schema.sql](../database/schema.sql) | Initial tables and foreign keys; no versioned migrations. |
| [database/seed.sql](../database/seed.sql) | Current demo fixture imported by Docker startup. |
| [database/adminInsert.sql](../database/adminInsert.sql), [teacherInsert.sql](../database/teacherInsert.sql), [scheduleManagerInsert.sql](../database/scheduleManagerInsert.sql) | Legacy standalone user insert scripts; not called by Docker bootstrap. |
| `storage/` | Generated logs, exports, and fallback sessions, ignored by Git. |
| [.gitignore](../.gitignore), [.dockerignore](../.dockerignore) | Ignore generated files, tools, and the root `.env`; alternate secret filenames need separate handling. |
| [.gitattributes](../.gitattributes) | Forces shell scripts to use LF line endings for Linux containers. |
| [.vscode/settings.json](../.vscode/settings.json) | Editor-specific workspace settings, not application runtime configuration. |
| [admin_credentials.txt](../admin_credentials.txt), [user-roles.txt](../user-roles.txt), [project_structure.txt](../project_structure.txt) | Compatibility pointers to maintained credential, role, and structure documentation. |
| [project_requirements.txt](../project_requirements.txt), [project_specification.txt](../project_specification.txt) | Original assignment and proposed specification, preserved as planning sources. |

Historical `.VSCodeCounter` reports are editor-generated snapshots, not current component or test documentation.
