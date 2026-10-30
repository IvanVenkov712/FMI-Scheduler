# Import and export formats

This reference describes [ImportService](app/services/ImportService.php), [ExportService](app/services/ExportService.php), and the registered controller actions. Use UTF-8 text and the separators shown below. Imports are intended for trusted administrators/schedule managers.

## Endpoints and access

| Method/path | Input or output |
| --- | --- |
| `POST /import-export/import-csv` | Multipart upload field `file` plus `type`; alternatively `file_path` on the server. |
| `POST /import-export/import-xml` | Multipart `file`, inline `xml`, or server `file_path`. Nonempty inline XML takes precedence. |
| `POST /import-export/import-ascii-plan` | `floor_id`, `ascii_plan` or server `file_path`, optional `sync_rooms` (default true). |
| `POST /floors/{id}/import-ascii-plan` | `ascii_plan` and optional `sync_rooms` for that floor. |
| `GET /floors/{id}/export-ascii-plan` | JSON object containing `ascii_plan`. |
| `GET /import-export/export-csv?type=rooms` | One model type as CSV; optional `filename`. |
| `GET /import-export/export-xml` | Six supported collections as XML; optional `filename`. |
| `GET /import-export/export-floor/{id}` | Human-readable floor text file. |
| `GET /import-export/export-building/{id}` | Human-readable building text file. |
| `GET /import-export/export-schedule/{id}` | Human-readable schedule text file. |

Imports require admin/schedule manager. Exports require any authenticated session. Browser file exports download the file; JSON clients usually receive `{"success":true,"data":{"path":"..."}}`. Paths refer to the **server filesystem**, not a downloadable client URL. XML requested as JSON without `filename` returns `{"xml":"..."}`.

Generated files go to `storage/exports`. CSV defaults to `{type}_all_{YYYYMMDD_HHMMSS}.csv`; XML to `export_{YYYYMMDD_HHMMSS}.xml`; text entity exports to `{type}_{id}_{YYYYMMDD_HHMMSS}.txt`. A supplied filename is reduced to its basename. Files with the same name are overwritten.

Example upload using a retained login cookie:

```sh
curl -b cookies.txt -H "Accept: application/json" -F "type=buildings" -F "file=@buildings.csv" http://localhost:8080/import-export/import-csv
```

## CSV

Supported `type` values are `building/buildings`, `floor/floors`, `room/rooms`, `resource/resources`, `event/events`, and `schedule/schedules`.

### Parsing and conversion

CSV uses PHP `fgetcsv`/`fputcsv` defaults: comma delimiter and double-quote enclosure. The first row is a header; names are trimmed and lowercased. Use UTF-8 without a byte-order mark: the parser does not remove a BOM from the first header. Quote fields containing commas, quotes, or newlines.

Only declared model fields are accepted. Unknown columns and input `id` are ignored. Imports **insert**, rather than updating matching records. Missing columns retain model defaults; supplied empty strings may be cast to null or fail database constraints. There is no complete application-level schema validation.

Integer conversion applies to `building_id, floor_id, room_id, requestor_id, online_access_id, capacity, floor_count, student_count, level`; an empty value becomes null. For `is_active`, `0, false, no, inactive` (case-insensitive) become false and other supplied strings become true. Referenced IDs must already exist in the destination database.

Each row is attempted independently. For example:

```json
{
  "success": false,
  "imported": 2,
  "errors": ["Line 4: Problem occurred while inserting."]
}
```

A failed response may still have inserted records. Duplicate unique keys and invalid foreign keys appear as row errors; a file-wide rollback is not performed.

### Buildings

```csv
name,code,address,campus_area,floor_count,is_active
FMI,FMI,5 James Bourchier Blvd,Lozenets,3,1
```

`code` must be unique. `floor_count` is an insertion limit for floors; set it before importing floors.

Export columns:

```csv
id,name,code,address,campus_area,floor_count,is_active
```

### Floors

```csv
building_id,level,name,ascii_plan,notes
1,0,Ground floor,[A101:Room A|30|physical|medium|1],Main entrance level
```

The building must exist. Building+level must be unique, and `FloorRepository` enforces the building's floor limit. Importing the `ascii_plan` field through CSV/XML only stores text; it does not run room synchronization.

Export columns:

```csv
id,building_id,level,name,ascii_plan,notes
```

### Rooms

```csv
floor_id,name,position_code,room_type,capacity,room_size,is_active
1,Room A,A101,physical,30,medium,1
```

The floor must exist. Floor+position code must be unique. Typical room types are `physical, online, hybrid`; sizes are `small, medium, large`, but imports do not apply all the CRUD form rules.

Export columns:

```csv
id,floor_id,name,position_code,room_type,capacity,room_size,is_active
```

The current model omits geometry/entrance/orientation columns present in the schema. Grid coordinate codes such as `"2,1"` need CSV quoting.

### Resources

```csv
name,description
Projector,Main classroom projector
```

Resource names must be unique. Room-resource quantities are not included.

Export columns:

```csv
id,name,description
```

### Events

```csv
requestor_id,room_id,online_access_id,name,type,start_time,end_time,delivery_mode,subject_name,student_count,status
1,2,,Database exam,exam,2026-11-16 09:00:00,2026-11-16 11:00:00,physical,Databases,30,pending
```

Requestor and nonempty room/online-access references must already exist. Use `YYYY-MM-DD HH:MM:SS` intervals. Typical types are `lecture, exam, practice, consultation`; modes are `physical, online, hybrid`; statuses used by the app include `pending, approved, rejected, cancelled`.

Import bypasses request/approval workflows: it does not attach resource requirements or validate room conflicts/online setup as those services do. Do not assume an imported approved event is a valid booking.

Export columns:

```csv
id,requestor_id,room_id,online_access_id,name,type,start_time,end_time,delivery_mode,subject_name,student_count,status
```

### Schedules

```csv
name,description,start_date,end_date
Autumn exams,Example exam period,2026-11-01,2026-11-30
```

Use `YYYY-MM-DD` dates. Schedule-event membership is not included.

Export columns:

```csv
id,name,description,start_date,end_date
```

Exports always include a header, even for an empty table. Boolean model values are written as 1/0, null as an empty field.

## XML

XML export root is `schedule_management_export`. Import accepts any root name with these direct child collections, processed in this order:

1. `buildings/building`
2. `floors/floor`
3. `rooms/room`
4. `resources/resource`
5. `events/event`
6. `schedules/schedule`

Node fields use the same model names and conversions as CSV. Example of inserting a building:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<schedule_management_export>
    <buildings>
        <building>
            <name>FMI</name>
            <code>FMI</code>
            <address>5 James Bourchier Blvd</address>
            <campus_area>Lozenets</campus_area>
            <floor_count>3</floor_count>
            <is_active>1</is_active>
        </building>
    </buildings>
</schedule_management_export>
```

Exports include IDs; import ignores them. Foreign keys are **not remapped** to newly inserted records. Collection order alone does not make a multi-entity export safe to restore into another database. Inspect destination IDs or import entities in separate reviewed steps.

XML import adds a `by_type` count map and reports per-record errors. Well-formed XML is required; no XSD is provided and malformed XML can generate PHP warnings. The parser is PHP SimpleXML; XML export is generated by the service's serializer.

## ASCII floor plans

ASCII plans are free text containing room tokens:

```text
[position_code:name|capacity|room_type|room_size|is_active]
```

Example:

```text
[A101:Room A|30|physical|medium|1]--[A102:Room B|20|physical|small|inactive]
     |
[LAB1:Computer Lab|25|physical|large|yes]
```

The shortest token `[A101]` defaults to name A101, capacity 0, type physical, size small, active true. A room token with a blank identity is ignored. `0, false, no, inactive` mark a token inactive; capacity becomes zero if nonnumeric. There is no escaping syntax for token delimiters `]`, `|`, or the identity separator `:`.

On ASCII import:

- The normalized text is stored in `floors.ascii_plan`.
- With `sync_rooms=true`, each token creates a room or updates an existing one with the same floor+position code.
- Existing rooms omitted from the plan are not deleted.
- Symbols outside tokens are preserved as text; `--` and `|` do not create route segments.
- With synchronization disabled, only the floor's stored text changes.
- The operation is not wrapped in a file-wide transaction.

Use a JSON boolean for `sync_rooms` on the general import endpoint. That action forwards the value directly; the string `"false"` is truthy in PHP. The floor-specific endpoint explicitly parses a form boolean.

ASCII export returns the stored nonempty plan with normalized newlines. If it is empty, the exporter builds room tokens from position code, name, capacity, and type, up to four rooms per line joined with `--`. This fallback omits size/active state and is not a lossless round trip.

The graphical grid uses coordinate position codes and is independent of the stored ASCII text. Dragging rooms does not rewrite the ASCII plan.

## Human-readable text and print views

Floor text exports contain the building/floor identity, ASCII plan, and room summaries including capacity, size, and active state. Building text exports contain building metadata followed by floor ASCII sections. Schedule text exports contain name/period/description and event summaries (time, delivery mode, room, count, status).

Separate `/events/{id}/export`, `/schedules/{id}/export`, and `/routes/export` pages render printable HTML. Browser print/PDF is separate from the plain text transfer files.

## Limitations

CSV/XML cover only buildings, floors, rooms, resources, events, and schedules. They omit:

- `users` and `audit_logs`
- `online_access`
- `room_resources` and `resource_requirements`
- `schedule_events`
- `route_segments` and `movement_constraints`
- Schema fields missing from the public models, including room geometry

References to omitted records can still appear in exported event rows. Transfers do not preserve IDs, do not reconstruct relationships, and may partly succeed. Use a complete MySQL dump for backup/restore; see [deployment](docs/DEPLOYMENT.md#backups-and-restore).
