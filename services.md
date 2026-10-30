# Service reference

These are the services implemented in [app/services](app/services), replacing the earlier proposed service list. Services receive optional constructor dependencies; omitted dependencies are created lazily or during construction. They contain business workflows, while controllers supply HTTP/session guards.

A method existing here does not imply a registered endpoint. Consult [HTTP_API.md](docs/HTTP_API.md) and [KNOWN_ISSUES.md](docs/KNOWN_ISSUES.md) before exposing additional service methods.

## Shared infrastructure

| Service | Contract |
| --- | --- |
| [BaseService](app/services/BaseService.php) | Creates default dependencies, normalizes roles/email/newlines, refreshes updated records, and wraps repository-local transactions. `abort($result)` throws the internal `ServiceAbort`, which the wrapper catches to roll back and return that result. Other exceptions roll back and propagate. |
| [DataTransferService](app/services/DataTransferService.php) | Shared constructor for building/floor/room/event/schedule/resource repositories and the export directory. It extends BaseService; ImportService and ExportService extend it. |

Transactions apply only to the PDO connection of the supplied repository. Other independently constructed repositories may use separate connections. Avoid assuming cross-repository atomicity or nesting support.

## Accounts and access

| Service | Main public operations and behavior |
| --- | --- |
| [LoginService](app/services/LoginService.php) | `login(identity, password)` looks up username/email, requires an active account, and uses `password_verify`. Returns a user or `null`; session creation belongs to the controller/helper. |
| [RegisterService](app/services/RegisterService.php) | `register(data)` normalizes identity/email, checks duplicates and nonempty password, hashes it, inserts an active student, and reloads it. Returns user/`null`; `errors()` and `error_message()` expose validation failures. Minimum password length is not enforced here. |
| [AuthorizationService](app/services/AuthorizationService.php) | `get_role`, `has_role`, `has_any_role`, `is_authorized`, named-role predicates, and `is_active` look up active users by identity. Most HTTP guards instead use SessionHelper's role snapshot. |
| [UserEventAccessService](app/services/UserEventAccessService.php) | `can_user_manage_event`: managers/admins or event requestor. `can_user_approve_event`: managers/admins except the requestor. `list_manageable_events` and `list_approvable_events` drive request lists. The approval controller does not call the self-approval check. |

## Event requests, approval, and online access

### EventRequestService

[Source](app/services/EventRequestService.php).

- `submit_event_request(event, requirements = [], online_access = null)`: normalize/validate event, resources, selected room, and meeting setup; persist a pending event with requirements/online details in an event-repository transaction; return the reloaded event or `null`.
- `edit_pending_request(event_id, new_event, new_requirements = [], new_online_access = null)`: only operates on a pending record, replacing event fields and resource requirements and updating/removing online access. This controller action currently has no route.
- `attach_resource_requirements` and `attach_online_access`: attach validated data to an eligible pending event.
- `errors()`: field-to-message map for the most recent request attempt.

Resource requirements accept arrays or objects with `resource_id`, `required_quantity`, and optional `notes`. Invalid or negative quantities fail; zero entries are ignored. Online data accepts an OnlineAccess-compatible object/array. Selected physical rooms are checked against approved bookings; pending requests do not reserve them.

### EventApprovalService

[Source](app/services/EventApprovalService.php).

- `approve_event_request(event_id)`: pending to approved after online-setup and physical booking checks; returns event/`null`.
- `reject_event_request(event_id)`: pending to rejected; returns event/`null`.
- `reschedule_approved_event(event_id)`: approved to pending; service-only in the current route table.

The service does not authorize the acting user. Callers must apply their own role/ownership rules. Booking checks and status changes are not protected by a shared room lock, leaving a concurrent approval race.

### OnlineAccessService

[Source](app/services/OnlineAccessService.php).

`provision_online_access(event_id, platform, link, key, host_notes)` stores valid meeting information for an online/hybrid event. `rotate_access_key(event_id)` generates a new stored key. `validate_hybrid_event_setup(event_id)` checks hybrid physical/online compatibility. These operations manage database records; they do not create meetings or rotate credentials through Zoom/Teams APIs.

### ExamSessionSetupService

[Source](app/services/ExamSessionSetupService.php).

`create_exam_session` validates an active requestor, subject, interval, count, delivery mode, requirements, and online setup. It creates a pending exam, attaches requirements, and, when a physical/hybrid room was omitted, calls best-room allocation. Failure to allocate aborts the transaction. Returns event/`null`.

`prepare_hybrid_exam` updates an existing exam's room and meeting setup. `validate_exam_capacity_and_resources` exposes room eligibility validation. Only exam creation currently has a registered HTTP route.

## Rooms and schedules

| Service | Main public operations and behavior |
| --- | --- |
| [RoomAvailabilityService](app/services/RoomAvailabilityService.php) | `is_room_bookable` checks valid interval/count, active room, capacity/resources, and no approved conflicts. `find_conflicting_events` returns approved overlaps. `find_eligible_rooms` permits omitted times, otherwise requires a valid pair, and sorts by capacity/ID. It does not itself filter delivery mode. |
| [RoomAllocationService](app/services/RoomAllocationService.php) | `rank_candidate_rooms(event_id)` returns entries with `room`, `score`, `capacity_surplus`, sorted by surplus/capacity/ID. `assign_best_room` updates the event to the first candidate. `reassign_room` tries a valid preferred room then falls back to ranking. Online-only events are excluded. Returns event/`null` for assignments. |
| [RoomResourceService](app/services/RoomResourceService.php) | `sync_room_resource(roomId, resourceId, quantity)` creates/updates an existing room/resource association, `remove_resource` removes it, and `room_meets_requirements` checks quantities. The corresponding sync controller action is unregistered. |
| [SchedulePublishingService](app/services/SchedulePublishingService.php) | `publish_event_to_schedule` optionally approves a pending event, checks schedule dates, and adds the join row; returns event/`null`. Repeated attachment returns the event. `remove_event_from_schedule` returns a boolean. `publish_batch` handles each item independently and returns only successful events. Approval may persist after a later publication failure. |

Time intervals use strict overlap; adjacent bookings do not conflict. Resource quantities are checked per room/event, without shared consumable inventory across different rooms.

## Routes and movement

### RoutePlanningService

[Source](app/services/RoutePlanningService.php).

`find_route(from, to, start, end)` returns the shortest directed path by the sum of nonnegative distances. `find_accessible_route` excludes stairs and applies timed segment usability when both times are given. Results are ordered RouteSegment arrays, `[]` for the same room, or `null` for invalid/unreachable endpoints.

`get_adjacent_rooms` lists active destinations of eligible outgoing segments. `get_route_distance(route)` resolves segments and totals distances, returning `null` for invalid input.

Search uses a linear-scan Dijkstra queue. Ordinary paths ignore timed constraints even when times are supplied. Distance is one scalar; the code has no distinct walking/car/transit travel-time fields.

### MovementConstraintService

[Source](app/services/MovementConstraintService.php).

- `is_route_segment_usable(segmentId, start, end)`: checks valid interval, segment availability, blocking rules, and nonpositive capacity restrictions.
- `is_valid_movement_plan(route, start, end)`: checks segment continuity, usability, and capacity.
- `has_capacity_violation(route, start, end)`: compares each route item's movement size with effective capacity.

Blocking types: `blocked`, `closed`, `closure`, `maintenance`, `unavailable`, `emergency`, `pandemic`, `no_entry`. Capacity types: `capacity`, `capacity_limit`, `limited_capacity`, `reduced_capacity`, `max_capacity`.

Effective capacity is the minimum applicable capacity, including a positive base corridor capacity. With no applicable capacity it is unbounded. Movement size is read from `movement_size`, `student_count`, `people_count`, `group_size`, or `quantity`, defaulting to one. This validates a supplied group; it does not aggregate simultaneous independent routes.

### RouteSegmentService

[Source](app/services/RouteSegmentService.php).

`resolve_route_segment(route_item)` accepts segment IDs, models, or supported array/object representations and returns a loaded segment or `null`. It centralizes resolution for distance and movement checks.

## Transfer and audit

| Service | Contract |
| --- | --- |
| [ImportService](app/services/ImportService.php) | CSV file, XML file/string, ASCII text/file import and token parsing. CSV/XML insert six supported model types and collect per-record errors; ASCII optionally creates/updates rooms by floor+position code. IDs are ignored. See [format reference](IMPORT_EXPORT_FORMATS.md). |
| [ExportService](app/services/ExportService.php) | Floor/building ASCII, floor/building/schedule text files, six-type CSV, six-collection XML, arbitrary text files. Uses model fields, basenames for filenames, and `storage/exports` by default. XML without a filename can return a string; most file operations return a path or `null`. |
| [AuditLogService](app/services/AuditLogService.php) | `record(request, user_id, username, details)` records successful mutation metadata; compacts/redacts array details and catches insert failures. It does not inherit BaseService. Audit failure does not abort the original operation. |

Import results include `success`, `imported`, and `errors`; ASCII adds `updated`/`rooms`, XML adds `by_type`. A false success value can accompany rows already inserted. Transfer exports are not database backups.
