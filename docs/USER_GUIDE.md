# User guide and roles

## Sign in and account settings

Use `/login` with a username or email. Local demo accounts are listed in the [README](../README.md#demo-accounts). `/register` creates an active student account. Browser registration returns to login; JSON registration also starts a session.

At `/users/profile`, update your email and optionally your password. The current password is required for both operations. A new password must have at least eight characters and match its confirmation. Administrators can change another user's role/status through `/users`; they cannot change their own role/status from that screen.

## Current permissions

These describe registered controller guards, not every rule envisioned by the original specification. Admin and schedule manager are separate roles with many shared operational permissions.

| Operation | Student | Teacher | Schedule manager | Admin |
| --- | --- | --- | --- | --- |
| View dashboard, buildings, floors/plans, rooms, events, schedules, routes, resources | Yes | Yes | Yes | Yes |
| Search room availability and routes | Yes | Yes | Yes | Yes |
| Export data and print reports | Yes | Yes | Yes | Yes |
| View users and user details | Yes | Yes | Yes | Yes |
| Update own profile | Yes | Yes | Yes | Yes |
| Submit requests/exams; generic event create/update/delete | No | Yes | Yes | Yes |
| Manage buildings/floors/rooms/resources/routes/constraints/schedules | No | No | Yes | Yes |
| Assign rooms, approve/reject requests, publish schedule events | No | No | Yes | Yes |
| CSV/XML/ASCII import | No | No | Yes | Yes |
| Change another user's role/status; view audit logs | No | No | No | Yes |

There is no implemented `technical_staff` role. Event writes are currently role-gated rather than consistently owner-gated, and existing sessions retain role/status snapshots; see [known issues](KNOWN_ISSUES.md#event-workflows-and-authorization).

## Model campus spaces

1. Create a building with a unique code and a sufficient `floor_count`.
2. Create floors with distinct levels within that building.
3. Add rooms with type, size, capacity, active status, and a unique position code within each floor.
4. Open `/floors/{id}/plan`. Admins/managers can drag rooms from the palette into the 12-by-5 grid. Other users can view it.
5. Use `/buildings/{id}/vertical` for North/South/East/West projections.

Grid placement replaces `position_code` with `column,row`, using zero-based coordinates. Removing a room from the grid assigns `_off_{id}`. Room names remain separate. ASCII text is stored independently and is not regenerated after dragging.

Resources have a catalog entry and per-room quantities. The service/repository layer supports inventory changes, but the room resource-sync action has no registered route; use reviewed database administration until that interface is completed.

## Submit and review an event request

Teachers, managers, and admins can submit through `/requests/create`. Provide event name/type, subject, start/end, student count, delivery mode, optional room, resource requirements, and online details as applicable.

- `physical`: requires a suitable physical/hybrid room for approval and has no meeting record.
- `online`: uses a valid platform and meeting URL, with no physical room in the request workflow.
- `hybrid`: combines room and meeting details.

Requests start pending. Only approved events block room availability; submitting a pending request does not reserve a room. Admins/managers review requests and approve or reject them. Approval rechecks room capacity, resources, interval conflicts, and meeting setup.

Use the request workflow for validation of resources and online details. Generic `/events` CRUD writes follow a different path and can bypass approval/booking checks; this is a [known defect](KNOWN_ISSUES.md#event-workflows-and-authorization).

## Find and allocate rooms

Use `/allocation/search` with student count, optional time interval, and resource quantities. Supplying either time requires a valid start/end pair. Results are sorted by capacity then ID.

Admin/manager assignment for an event selects the eligible physical/hybrid room with the smallest surplus capacity. It checks approved-event conflicts and resource quantities. This assignment does not itself approve the event or publish it in a schedule.

## Build schedules

Create a schedule with a name, description, start date, and end date. Current validation requires end date strictly after start date.

Open its details and add an event. Publication can approve a pending event and then attach it if it fits the schedule's dates. A failed attachment may still leave the event approved. Removing it from a schedule does not delete the event or free an approved room booking.

The calendar route displays the same details view; the app does not include a separate interactive calendar engine. Event and schedule export pages are printable HTML; use the browser's print/PDF feature.

## Navigate between rooms

Admin/manager users create directed room-to-room route segments with a distance and optional stairs/elevator/capacity flags. Add the reverse edge separately if travel is allowed both ways.

Route search finds the lowest sum of segment distances. Accessible mode excludes stairs; include both start/end times to apply supported timed closures in that mode. Ordinary search ignores timed constraints. Accessibility here is limited to those modeled flags and constraints.

Movement constraints can express closures or reduced capacity. The current `/constraints` browser listing reuses the route template and has presentation gaps; see [known issues](KNOWN_ISSUES.md#interface-and-model-gaps).

## Import, export, and audit

Managers/admins can upload CSV/XML, paste XML, and import ASCII plans. Imports can partly succeed: inspect `imported` and `errors` rather than assuming failure rolled everything back. Reimporting CSV/XML inserts new records and does not preserve IDs.

All signed-in roles can export. Text exports cover floors/buildings/schedules; CSV/XML cover six entity types. See [IMPORT_EXPORT_FORMATS.md](../IMPORT_EXPORT_FORMATS.md) for exact formats and omissions. Use database backups for restore.

Administrators can view `/audit-logs`, filtering by action, username, and dates. Audit writes are best effort, so a successful mutation can remain successful when audit storage fails. Operational file logs and audit records are separate.
