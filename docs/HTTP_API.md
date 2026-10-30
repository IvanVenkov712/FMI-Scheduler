# HTTP interface

[config/routes.php](../config/routes.php) is authoritative. The application serves HTML and JSON through the same paths; there is no separate registered `/api` prefix. Examples use the local Docker address.

## Authentication and negotiation

Authentication uses PHP session cookies, not bearer tokens. Sign in with `identity` (username/email) and `password`, retain the cookie, and send it on later requests. JSON may be requested with `Accept: application/json`, `X-Requested-With: XMLHttpRequest`, or `?format=json`.

These curl commands use form fields and also work as `curl.exe` in PowerShell:

```sh
curl -c cookies.txt -H "Accept: application/json" --data-urlencode "identity=teacher" --data-urlencode "password=Abc123" http://localhost:8080/login
curl -b cookies.txt -H "Accept: application/json" http://localhost:8080/auth/me
curl -b cookies.txt -H "Accept: application/json" http://localhost:8080/rooms
curl -b cookies.txt -H "Accept: application/json" -X POST http://localhost:8080/logout
```

Treat cookie files as credentials and keep them outside version control. Examples use public demo accounts.

Input supports query parameters, URL-encoded/multipart forms, and JSON when `Content-Type` contains `application/json`. JSON body values override form/query values in `payload()`. Native form POST can use `_method=PUT` or `_method=DELETE`. Raw URL-encoded PUT bodies are not parsed; use JSON or the form override.

## Responses

Responses are not uniformly wrapped:

- `respond()` in JSON mode returns the view-data object directly, such as `{"rooms":[...],"filters":{...}}`.
- Mutation helpers usually return `{"success":true,"data":{...}}`, using 201 for creation.
- Controller errors usually return `{"success":false,"message":"...","errors":{...}}`. Empty errors may encode as `[]`.
- Import results expose `success`, `imported`, `errors`, and optional counts directly.
- File exports return downloads to browsers, but often return a server-side `path` to JSON clients. XML export without `filename` returns `{"xml":"..."}` in JSON mode.
- Browser mutations generally redirect with flash messages. Use JSON negotiation to inspect the validation/authorization status without a redirect.

Common JSON statuses are 401 unauthenticated, 403 wrong role, 404 missing route/record, 409 duplicate/in-use record, 422 invalid input/workflow, and 500 unexpected error. Framework errors use their own safe message shape.

Sensitive-data filtering has [known object-serialization defects](KNOWN_ISSUES.md#logging-and-serialization); clients must not assume all returned fields are safe to expose.

## Access rules

Login and registration are public. Other normal pages/read operations require a session. Building/floor/room/resource/route/constraint/schedule writes, allocation, imports, approval, and publication generally require admin or schedule manager. Event writes, requests, and exam creation additionally allow teachers. Profile changes apply to the signed-in user; user role/status changes and audit reads require admin. See the [permission matrix](USER_GUIDE.md#current-permissions) and authorization issues.

## Useful payloads

### Create a request

Use `POST /requests` or `POST /events/submit-request` with this JSON structure. Substitute existing room/resource/user IDs and a suitable interval.

```json
{
  "name": "Database exam",
  "type": "exam",
  "subject_name": "Databases",
  "start_time": "2026-11-16 09:00:00",
  "end_time": "2026-11-16 11:00:00",
  "delivery_mode": "physical",
  "student_count": 30,
  "room_id": 1,
  "resource_requirements": [
    {"resource_id": 1, "required_quantity": 1, "notes": "Projector"}
  ]
}
```

The session user is used when `requestor_id` is omitted. For online/hybrid delivery add:

```json
{
  "online_access": {
    "platform": "Zoom",
    "meeting_link": "https://example.com/meeting",
    "access_key": "example-only",
    "host_notes": ""
  }
}
```

This fragment is merged into the request object. Online-only requests omit the physical room; hybrid requests use both. Platform information is stored locally; there is no provider API call.

### Availability, allocation, and publication

| Endpoint | Body |
| --- | --- |
| `POST /rooms/{id}/availability` | `start_time`, `end_time`, `student_count`, optional `resource_requirements`; returns bookable/conflicts. |
| `POST /allocation/search` | Same search fields; times may both be omitted. |
| `POST /allocation/{id}/assign` | Event ID in the path; assigns best eligible room. |
| `POST /events/{id}/approve` or `/reject` | Event ID in path; no additional body required. |
| `POST /schedules/{id}/publish-event` or `/remove-event` | `event_id`; schedule ID in path. |
| `POST /rooms/{id}/position` | `{"col":2,"row":1}`; omit coordinates to unplace the room. |

### Route search

`POST /routes/find` accepts `from_room_id`, `to_room_id`, optional boolean `accessible_only`, and optional `start_time`/`end_time`. A JSON result contains ordered route segments and their total distance. Both times are needed to apply timed restrictions in accessible mode. Ordinary search ignores them.

`GET /routes/export` takes the same values as query parameters and renders a printable report.

### Account operations

| Endpoint | Fields |
| --- | --- |
| `POST /register` | `username`, `email`, `password`, `password_confirmation`, optional `first_name`/`last_name`. |
| `POST /users/profile` | `email`, `current_password`, optional `new_password`/`new_password_confirmation`. |
| `POST /users/{id}/role-status` | `role` in student/teacher/schedule_manager/admin; `is_active` as 1/true/on to activate. Admin only; cannot target yourself. |

CSV/XML/ASCII bodies and export behavior are documented in [IMPORT_EXPORT_FORMATS.md](../IMPORT_EXPORT_FORMATS.md).

## List filters

Filters are query parameters. Unsupported keys are ignored by controller whitelists or repositories.

| List | Accepted controller filters |
| --- | --- |
| Buildings | `q, name, code, status` |
| Floors | `q, name, building_id, level` |
| Rooms | `q, name, position_code, building_id, floor_id, room_type, room_size, capacity_min, status` |
| Events | `q, name, building_id, floor_id, room_id, type, delivery_mode, status, date_from, date_to` |
| Requests | `q, name, building_id, floor_id, room_id, delivery_mode, status, date_from, date_to` |
| Schedules | `q, name, date_from, date_to` |
| Routes | `q, building_id, floor_id, segment_room_id, segment_from_room_id, segment_to_room_id, access, status` |
| Resources | `q, name` |
| Users | `q, role, status` |
| Audit logs | `action, username, date_from, date_to` |

Use `YYYY-MM-DD` for date filters and `YYYY-MM-DD HH:MM:SS` for event intervals. Active/inactive and stairs/elevator filter values are defined by the relevant repository/form. There is no general pagination contract.

## Registered routes

This inventory mirrors `config/routes.php`. POST update/delete aliases support browser forms alongside PUT/DELETE. IDs are path parameters; their existence and authorization are checked by the action.

| Method | Path | Controller action |
| --- | --- | --- |
| `GET` | `/` | `DashboardController::index` |
| `GET` | `/dashboard` | `DashboardController::index` |
| `GET` | `/login` | `AuthController::login_form` |
| `POST` | `/login` | `AuthController::login` |
| `POST` | `/logout` | `AuthController::logout` |
| `GET` | `/register` | `AuthController::register_form` |
| `POST` | `/register` | `AuthController::register` |
| `GET` | `/auth/me` | `AuthController::me` |
| `GET` | `/buildings` | `BuildingController::index` |
| `GET` | `/buildings/create` | `BuildingController::create` |
| `POST` | `/buildings` | `BuildingController::store` |
| `GET` | `/buildings/{id}` | `BuildingController::show` |
| `GET` | `/buildings/{id}/edit` | `BuildingController::edit` |
| `PUT` | `/buildings/{id}` | `BuildingController::update` |
| `POST` | `/buildings/{id}/update` | `BuildingController::update` |
| `DELETE` | `/buildings/{id}` | `BuildingController::destroy` |
| `POST` | `/buildings/{id}/delete` | `BuildingController::destroy` |
| `GET` | `/buildings/{id}/floors` | `BuildingController::floors` |
| `GET` | `/buildings/{id}/vertical` | `BuildingController::vertical` |
| `GET` | `/floors` | `FloorController::index` |
| `GET` | `/floors/create` | `FloorController::create` |
| `POST` | `/floors` | `FloorController::store` |
| `GET` | `/floors/{id}` | `FloorController::show` |
| `GET` | `/floors/{id}/edit` | `FloorController::edit` |
| `PUT` | `/floors/{id}` | `FloorController::update` |
| `POST` | `/floors/{id}/update` | `FloorController::update` |
| `DELETE` | `/floors/{id}` | `FloorController::destroy` |
| `POST` | `/floors/{id}/delete` | `FloorController::destroy` |
| `GET` | `/floors/{id}/rooms` | `FloorController::rooms` |
| `GET` | `/floors/{id}/plan` | `FloorController::plan` |
| `POST` | `/floors/{id}/import-ascii-plan` | `FloorController::import_ascii_plan` |
| `GET` | `/floors/{id}/export-ascii-plan` | `FloorController::export_ascii_plan` |
| `GET` | `/rooms` | `RoomController::index` |
| `GET` | `/rooms/create` | `RoomController::create` |
| `POST` | `/rooms` | `RoomController::store` |
| `GET` | `/rooms/{id}` | `RoomController::show` |
| `GET` | `/rooms/{id}/edit` | `RoomController::edit` |
| `PUT` | `/rooms/{id}` | `RoomController::update` |
| `POST` | `/rooms/{id}/update` | `RoomController::update` |
| `DELETE` | `/rooms/{id}` | `RoomController::destroy` |
| `POST` | `/rooms/{id}/delete` | `RoomController::destroy` |
| `POST` | `/rooms/{id}/availability` | `RoomController::availability` |
| `POST` | `/rooms/{id}/position` | `RoomController::update_position` |
| `GET` | `/rooms/{id}/resources` | `RoomController::resources` |
| `GET` | `/rooms/{id}/events` | `RoomController::events` |
| `GET` | `/schedules` | `ScheduleController::index` |
| `GET` | `/schedules/create` | `ScheduleController::create` |
| `POST` | `/schedules` | `ScheduleController::store` |
| `GET` | `/schedules/{id}/export` | `ScheduleController::export` |
| `GET` | `/schedules/{id}` | `ScheduleController::show` |
| `GET` | `/schedules/{id}/calendar` | `ScheduleController::calendar` |
| `PUT` | `/schedules/{id}` | `ScheduleController::update` |
| `POST` | `/schedules/{id}/update` | `ScheduleController::update` |
| `DELETE` | `/schedules/{id}` | `ScheduleController::destroy` |
| `POST` | `/schedules/{id}/delete` | `ScheduleController::destroy` |
| `POST` | `/schedules/{id}/publish-event` | `ScheduleController::publish_event` |
| `POST` | `/schedules/{id}/remove-event` | `ScheduleController::remove_event` |
| `GET` | `/events` | `EventController::index` |
| `GET` | `/events/create` | `EventController::create` |
| `POST` | `/events` | `EventController::store` |
| `GET` | `/events/{id}/export` | `EventController::export` |
| `GET` | `/events/{id}` | `EventController::show` |
| `GET` | `/events/{id}/edit` | `EventController::edit` |
| `PUT` | `/events/{id}` | `EventController::update` |
| `POST` | `/events/{id}/update` | `EventController::update` |
| `DELETE` | `/events/{id}` | `EventController::destroy` |
| `POST` | `/events/{id}/delete` | `EventController::destroy` |
| `POST` | `/events/{id}/approve` | `EventController::approve` |
| `POST` | `/events/{id}/reject` | `EventController::reject` |
| `POST` | `/events/submit-request` | `EventController::submit_request` |
| `GET` | `/requests` | `RequestController::index` |
| `GET` | `/requests/create` | `RequestController::create` |
| `POST` | `/requests` | `RequestController::store` |
| `GET` | `/requests/{id}/review` | `RequestController::review` |
| `GET` | `/resources` | `ResourceController::index` |
| `POST` | `/resources` | `ResourceController::store` |
| `GET` | `/routes` | `RouteController::index` |
| `GET` | `/routes/map` | `RouteController::map` |
| `GET` | `/routes/export` | `RouteController::export` |
| `POST` | `/routes` | `RouteController::store` |
| `GET` | `/routes/{id}` | `RouteController::show` |
| `POST` | `/routes/find` | `RouteController::find` |
| `GET` | `/allocation/search` | `AllocationController::search_form` |
| `POST` | `/allocation/search` | `AllocationController::search` |
| `POST` | `/allocation/{id}/assign` | `AllocationController::assign` |
| `GET` | `/import-export/import` | `ImportExportController::import` |
| `GET` | `/import-export/export` | `ImportExportController::export` |
| `POST` | `/import-export/import-csv` | `ImportExportController::import_csv` |
| `POST` | `/import-export/import-xml` | `ImportExportController::import_xml` |
| `POST` | `/import-export/import-ascii-plan` | `ImportExportController::import_ascii_plan` |
| `GET` | `/import-export/export-floor/{id}` | `ImportExportController::export_floor` |
| `GET` | `/import-export/export-building/{id}` | `ImportExportController::export_building` |
| `GET` | `/import-export/export-schedule/{id}` | `ImportExportController::export_schedule` |
| `GET` | `/import-export/export-csv` | `ImportExportController::export_csv` |
| `GET` | `/import-export/export-xml` | `ImportExportController::export_xml` |
| `GET` | `/users` | `UserController::index` |
| `GET` | `/users/profile` | `UserController::profile` |
| `POST` | `/users/profile` | `UserController::update_profile` |
| `POST` | `/users/{id}/role-status` | `UserController::update_role_status` |
| `GET` | `/users/{id}` | `UserController::show` |
| `GET` | `/audit-logs` | `AuditLogController::index` |
| `GET` | `/constraints` | `MovementConstraintController::index` |
| `POST` | `/constraints` | `MovementConstraintController::store` |
| `GET` | `/exam-sessions/create` | `ExamSessionController::create` |
| `POST` | `/exam-sessions` | `ExamSessionController::store` |

## Methods without registered routes

The following controller methods currently have no HTTP mapping: `RequestController::update`; `RoomController::sync_resource`; `EventController::reschedule/attach_requirements/provision_online_access`; `AllocationController::candidates/reassign/auto_assign`; `RouteController::accessible/adjacent`; `MovementConstraintController::usable/overlapping`; `ExamSessionController::prepare_hybrid/validate_room`; and `UserController::events`.

Use `accessible_only` with the registered `/routes/find` route for accessible search. Review authorization before registering other actions.
