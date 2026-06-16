# Формати на import/export файловете

Този документ описва файловите формати, които текущата система реално чете и генерира чрез `ImportService` и `ExportService`.

## Общи правила

- Текстовите export файлове се записват в `storage/exports`.
- Имената на автоматично генерираните export файлове са:
  - `floor_{id}_{YYYYMMDD_HHMMSS}.txt`
  - `building_{id}_{YYYYMMDD_HHMMSS}.txt`
  - `schedule_{id}_{YYYYMMDD_HHMMSS}.txt`
  - `{type}_all_{YYYYMMDD_HHMMSS}.csv`
  - `export_{YYYYMMDD_HHMMSS}.xml`
- При запис новите редове се нормализират до `\n`.
- Import на CSV и XML винаги създава нови записи. Не се прави update по `id`.
- Полето `id` се игнорира при CSV/XML import, дори ако присъства.
- Непознати полета в CSV/XML се игнорират.
- Булевото поле `is_active` се счита за `false`, ако стойността е една от: `0`, `false`, `no`, `inactive`. Всички други стойности се третират като `true`.

## CSV import

CSV import се подава към `/import-export/import-csv` с:

- `type` - типът данни
- `file` - `.csv` файл

CSV файлът използва стандартния PHP `fgetcsv` формат:

- разделител: запетая `,`
- enclosure: двойни кавички `"`
- първият ред е header
- имената на колоните се нормализират само с `trim` и `lowercase`
- стойности със запетаи трябва да са в двойни кавички

Поддържаните стойности за `type` са както singular, така и plural форми:

- `building` / `buildings`
- `floor` / `floors`
- `room` / `rooms`
- `resource` / `resources`
- `event` / `events`
- `schedule` / `schedules`

В UI към момента са показани `rooms`, `events`, `schedules` и `resources`, но service слоят поддържа и `buildings` и `floors`.

### CSV полета по тип

#### buildings

```csv
name,code,address,campus_area,floor_count,is_active
FMI,101,Lozenec,camp,3,1
```

Полета:

- `name` - задължително на ниво база
- `code` - задължително и уникално на ниво база
- `address`
- `campus_area`
- `floor_count` - цяло число
- `is_active` - булева стойност

#### floors

```csv
building_id,level,name,ascii_plan,notes
1,0,F0,"[A101:Room A|30|physical|medium|1]","Ground floor"
```

Полета:

- `building_id` - цяло число, трябва да сочи към съществуваща сграда
- `level` - цяло число
- `name`
- `ascii_plan`
- `notes`

Ограничение в базата: комбинацията `building_id + level` трябва да е уникална.

#### rooms

```csv
floor_id,name,position_code,room_type,capacity,room_size,is_active
1,Room A,A101,physical,30,medium,1
```

Полета:

- `floor_id` - цяло число, трябва да сочи към съществуващ етаж
- `name`
- `position_code`
- `room_type` - например `physical`, `online`, `hybrid`
- `capacity` - цяло число
- `room_size` - например `small`, `medium`, `large`
- `is_active` - булева стойност

Ограничение в базата: комбинацията `floor_id + position_code` трябва да е уникална.

#### resources

```csv
name,description
Projector,Main classroom projector
```

Полета:

- `name` - задължително и уникално на ниво база
- `description`

#### events

```csv
requestor_id,room_id,online_access_id,name,type,start_time,end_time,delivery_mode,subject_name,student_count,status
1,2,,Exam 1,exam,2026-06-20 09:00:00,2026-06-20 11:00:00,physical,Databases,45,pending
```

Полета:

- `requestor_id` - цяло число, трябва да сочи към съществуващ потребител
- `room_id` - цяло число или празно
- `online_access_id` - цяло число или празно
- `name`
- `type` - например `lecture`, `exam`
- `start_time` - `YYYY-MM-DD HH:MM:SS`
- `end_time` - `YYYY-MM-DD HH:MM:SS`
- `delivery_mode` - например `physical`, `online`, `hybrid`
- `subject_name`
- `student_count` - цяло число
- `status` - например `pending`, `approved`, `rejected`

#### schedules

```csv
name,description,start_date,end_date
Summer exams,June session,2026-06-01,2026-06-30
```

Полета:

- `name`
- `description`
- `start_date` - `YYYY-MM-DD`
- `end_date` - `YYYY-MM-DD`

## CSV export

CSV export се генерира от `/import-export/export-csv` с query параметър:

- `type` - типът данни за export

Поддържаните стойности са същите като при CSV import:

- `building` / `buildings`
- `floor` / `floors`
- `room` / `rooms`
- `resource` / `resources`
- `event` / `events`
- `schedule` / `schedules`

Пример:

```text
/import-export/export-csv?type=rooms
```

По подразбиране файлът се записва в `storage/exports` с име:

```text
{type}_all_{YYYYMMDD_HHMMSS}.csv
```

Например:

```text
rooms_all_20260616_203000.csv
```

Ако е подаден параметър `filename`, той се използва като име на файла:

```text
/import-export/export-csv?type=rooms&filename=my_rooms.csv
```

### CSV export формат

CSV export използва стандартния PHP `fputcsv` формат:

- разделител: запетая `,`
- enclosure: двойни кавички `"`
- първият ред винаги е header
- ако няма записи от избрания тип, файлът съдържа само header ред
- булевите стойности се записват като `1` или `0`

Колоните са имената на публичните полета в съответния model и включват `id`. Това е разлика спрямо CSV import, където `id` се игнорира.

### CSV export колони по тип

#### buildings

```csv
id,name,code,address,campus_area,floor_count,is_active
```

#### floors

```csv
id,building_id,level,name,ascii_plan,notes
```

#### rooms

```csv
id,floor_id,name,position_code,room_type,capacity,room_size,is_active
```

#### resources

```csv
id,name,description
```

#### events

```csv
id,requestor_id,room_id,online_access_id,name,type,start_time,end_time,delivery_mode,subject_name,student_count,status
```

#### schedules

```csv
id,name,description,start_date,end_date
```

### CSV export ограничения

CSV export включва само един избран тип данни на файл. Не се export-ват свързващи таблици като `schedule_events`, `room_resources` и `resource_requirements`.

CSV export файловете са удобни за преглед и частичен import, но не са пълен backup/restore формат, защото при повторен CSV import `id` се игнорира и се създават нови записи.

## XML import/export

XML export се генерира от `/import-export/export-xml`.

Ако не е подадено име на файл, отговорът съдържа XML като текст. Ако е подаден `filename`, XML се записва в `storage/exports`.

### XML структура

Export root е:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<schedule_management_export>
    ...
</schedule_management_export>
```

Import не изисква конкретно име на root елемента, но очаква следните колекции като директни child елементи:

- `buildings`
- `floors`
- `rooms`
- `resources`
- `events`
- `schedules`

Редът на import е фиксиран:

1. `buildings`
2. `floors`
3. `rooms`
4. `resources`
5. `events`
6. `schedules`

Това позволява зависимостите `building_id`, `floor_id` и `room_id` да сочат към вече вмъкнати записи, ако ID-тата в базата съвпадат с очакваните стойности. Понеже `id` се игнорира при import, XML export не е пълноценен backup/restore формат за връзките между таблиците.

### XML пример

```xml
<?xml version="1.0" encoding="UTF-8"?>
<schedule_management_export>
    <buildings>
        <building>
            <id>1</id>
            <name>FMI</name>
            <code>101</code>
            <address>Lozenec</address>
            <campus_area>camp</campus_area>
            <floor_count>3</floor_count>
            <is_active>1</is_active>
        </building>
    </buildings>
    <floors>
        <floor>
            <id>1</id>
            <building_id>1</building_id>
            <level>0</level>
            <name>F0</name>
            <ascii_plan>[A101:Room A|30|physical|medium|1]</ascii_plan>
            <notes>Ground floor</notes>
        </floor>
    </floors>
    <rooms>
        <room>
            <id>1</id>
            <floor_id>1</floor_id>
            <name>Room A</name>
            <position_code>A101</position_code>
            <room_type>physical</room_type>
            <capacity>30</capacity>
            <room_size>medium</room_size>
            <is_active>1</is_active>
        </room>
    </rooms>
    <resources>
        <resource>
            <id>1</id>
            <name>Projector</name>
            <description>Main classroom projector</description>
        </resource>
    </resources>
    <events>
        <event>
            <id>1</id>
            <requestor_id>1</requestor_id>
            <room_id>1</room_id>
            <online_access_id></online_access_id>
            <name>Exam 1</name>
            <type>exam</type>
            <start_time>2026-06-20 09:00:00</start_time>
            <end_time>2026-06-20 11:00:00</end_time>
            <delivery_mode>physical</delivery_mode>
            <subject_name>Databases</subject_name>
            <student_count>45</student_count>
            <status>pending</status>
        </event>
    </events>
    <schedules>
        <schedule>
            <id>1</id>
            <name>Summer exams</name>
            <description>June session</description>
            <start_date>2026-06-01</start_date>
            <end_date>2026-06-30</end_date>
        </schedule>
    </schedules>
</schedule_management_export>
```

### XML export ограничения

XML export включва само:

- `buildings`
- `floors`
- `rooms`
- `resources`
- `events`
- `schedules`

Не се export-ват:

- `users`
- `room_resources`
- `online_access`
- `resource_requirements`
- `route_segments`
- `movement_constraints`
- `schedule_events`
- `audit_logs`

## ASCII floor plan import/export

ASCII планът е свободен текст, в който стаите се разпознават чрез tokens в квадратни скоби.

### Token формат

```text
[position_code:name|capacity|room_type|room_size|is_active]
```

Полета:

- `position_code` - код на позицията в етажа
- `name` - име на залата
- `capacity` - цяло число
- `room_type` - например `physical`, `online`, `hybrid`
- `room_size` - например `small`, `medium`, `large`
- `is_active` - незадължително; `0`, `false`, `no`, `inactive` означават неактивна стая

Минималният валиден token е:

```text
[A101]
```

Той се интерпретира като:

- `position_code`: `A101`
- `name`: `A101`
- `capacity`: `0`
- `room_type`: `physical`
- `room_size`: `small`
- `is_active`: `true`

Пълен пример:

```text
[A101:Room A|30|physical|medium|1]--[A102:Room B|20|physical|small|inactive]
     |
[LAB1:Computer Lab|25|physical|large|yes]
```

При import:

- Целият ASCII план се записва в `floors.ascii_plan`.
- Всички tokens от вида `[ ... ]` се парсват като стаи.
- Ако `sync_rooms` е включено, системата:
  - създава нова стая, ако няма стая със същия `position_code` на този етаж
  - обновява `name`, `room_type`, `capacity`, `room_size`, `is_active`, ако такава стая вече съществува
- Визуални символи извън tokens, например `--`, `|`, интервали и нови редове, се пазят само като част от `ascii_plan`.

### ASCII export на етаж

Ако етажът има непразно поле `ascii_plan`, export-ът връща точно него с нормализирани нови редове.

Ако `ascii_plan` е празен, системата генерира план от наличните стаи:

```text
[A101:Room A|30|physical]--[A102:Room B|20|physical]
[A103:Room C|40|physical]
```

При автоматично генерирания план:

- има до 4 стаи на ред
- tokens са разделени с `--`
- включват се само `position_code`, `name`, `capacity`, `room_type`
- не се включват `room_size` и `is_active`

## Text export на етаж

Text export на етаж се записва като `.txt` файл.

Формат:

```text
Floor export
Building: {building_name} ({building_code})
Floor: {floor_name} / level {level}

ASCII plan:
{ascii_plan}

Rooms:
- {room_name} [{position_code}], type: {room_type}, capacity: {capacity}, size: {room_size}, active: {yes|no}
```

Пример:

```text
Floor export
Building: FMI (101)
Floor: F0 / level 0

ASCII plan:
[A101:Room A|30|physical|medium|1]

Rooms:
- Room A [A101], type: physical, capacity: 30, size: medium, active: yes
```

## Text export на сграда

Text export на сграда се записва като `.txt` файл.

Формат:

```text
Building export
Name: {building_name}
Code: {building_code}
Address: {address}
Campus area: {campus_area}
Floors: {floor_count}

BUILDING {building_code} - {building_name}

FLOOR {level} - {floor_name}
{floor_ascii_plan}
```

Пример:

```text
Building export
Name: FMI
Code: 101
Address: Lozenec
Campus area: camp
Floors: 3

BUILDING 101 - FMI

FLOOR 0 - F0
[A101:Room A|30|physical|medium|1]
```

## Text export на график

Text export на график се записва като `.txt` файл.

Формат:

```text
Schedule export
Name: {schedule_name}
Period: {start_date} - {end_date}
Description: {description}

Events:
- {event_name} ({subject_name}), {start_time} - {end_time}, mode: {delivery_mode}, room: {room_name|N/A}, students: {student_count}, status: {status}
```

Пример:

```text
Schedule export
Name: Summer exams
Period: 2026-06-01 - 2026-06-30
Description: June session

Events:
- Exam 1 (Databases), 2026-06-20 09:00:00 - 2026-06-20 11:00:00, mode: physical, room: Room A, students: 45, status: pending
```
