<?php

declare(strict_types=1);

class ImportService
{
    public function __construct(
        private readonly CsvParser $csv = new CsvParser(),
        private readonly XmlDataParser $xml = new XmlDataParser(),
        private readonly AsciiPlanParser $ascii = new AsciiPlanParser(),
        private readonly BuildingService $buildings = new BuildingService(),
        private readonly FloorService $floors = new FloorService(),
        private readonly RoomService $rooms = new RoomService(),
        private readonly EventService $events = new EventService(),
        private readonly ResourceService $resources = new ResourceService(),
        private readonly RouteService $routes = new RouteService(),
        private readonly ScheduleService $schedules = new ScheduleService(),
        private readonly CourseService $courses = new CourseService(),
        private readonly StudentGroupService $studentGroups = new StudentGroupService(),
        private readonly AllocationParameterService $allocationParameters = new AllocationParameterService(),
        private readonly RequestService $requests = new RequestService(),
        private readonly CovidConstraintService $movementConstraints = new CovidConstraintService(),
        private readonly UserService $users = new UserService(),
        private readonly UserRepository $userRepository = new UserRepository(),
        private readonly BuildingRepository $buildingRepository = new BuildingRepository(),
        private readonly FloorRepository $floorRepository = new FloorRepository(),
        private readonly ScheduleRepository $scheduleRepository = new ScheduleRepository(),
        private readonly CourseRepository $courseRepository = new CourseRepository(),
        private readonly StudentGroupRepository $studentGroupRepository = new StudentGroupRepository(),
        private readonly AllocationParameterRepository $allocationParameterRepository = new AllocationParameterRepository(),
        private readonly ?Database $database = null
    ) {
    }

    public function importFile(string $entity, string $format, string $filePath, ?int $userId = null, array $context = []): array
    {
        $jobId = $this->startJob('import', $format, $entity, $filePath, $userId);

        try {
            $result = match ($format) {
                'csv' => $this->importRows($entity, $this->csv->parseFile($filePath), $userId),
                'xml' => $this->importRows($entity, $this->xml->parseFile($filePath, $this->xmlRowTag($entity)), $userId),
                'ascii', 'txt' => $this->importAscii($entity, $filePath, $userId, $context),
                'json' => $this->importRows($entity, $this->decodeJsonFile($filePath), $userId),
                default => throw new InvalidArgumentException('Unsupported import format.'),
            };

            $this->finishJob($jobId, 'completed', $this->summaryMessage($result));

            return ['success' => true, 'job_id' => $jobId] + $result;
        } catch (Throwable $exception) {
            $this->finishJob($jobId, 'failed', $exception->getMessage());

            return ['success' => false, 'job_id' => $jobId, 'errors' => ['import' => [$exception->getMessage()]]];
        }
    }

    public function importRows(string $entity, array $rows, ?int $userId = null): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $errors[] = ['row' => $index + 1, 'message' => 'Row must be an object/array.'];
                continue;
            }

            $row = SensitiveData::strip($row);

            $result = match ($entity) {
                'buildings' => $this->buildings->create($row, $userId),
                'floors' => $this->floors->create($row, $userId),
                'rooms' => $this->rooms->create($row, [], $userId),
                'events' => $this->events->create($row, null, $userId),
                'resources' => $this->resources->create($row, $userId),
                'routes' => $this->routes->create($row, [], $userId),
                'schedules' => $this->importSchedule($row, $userId),
                'courses' => $this->importCourse($row, $userId),
                'student_groups' => $this->importStudentGroup($row, $userId),
                'students' => $this->importStudent($row, $userId),
                'allocation_parameters' => $this->importAllocationParameter($row, $userId),
                'exam_requests' => $this->importExamRequest($row, $userId),
                'resource_requests' => $this->importResourceRequest($row, $userId),
                'online_sessions' => $this->importOnlineSession($row, $userId),
                'movement_constraints' => $this->importMovementConstraint($row, $userId),
                default => throw new InvalidArgumentException('Unsupported import entity.'),
            };

            if (!($result['success'] ?? false)) {
                $errors[] = ['row' => $index + 1, 'message' => $this->flattenErrors((array) ($result['errors'] ?? []))];
                continue;
            }

            $imported++;
        }

        return ['imported' => $imported, 'failed' => count($errors), 'errors' => $errors];
    }

    public function importAscii(string $entity, string $filePath, ?int $userId = null, array $context = []): array
    {
        if (!in_array($entity, ['floors', 'buildings'], true)) {
            throw new InvalidArgumentException('ASCII import is supported only for building and floor plans.');
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException('ASCII plan file could not be read.');
        }

        $trimmed = ltrim($content);
        if ($entity === 'buildings' || str_starts_with($trimmed, 'BUILDING ')) {
            return $this->importBuildingAsciiDocuments($content, $userId);
        }

        return $this->importFloorAsciiDocuments($content, $userId, $context);
    }

    private function decodeJsonFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException('JSON file could not be read.');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('JSON file does not contain a valid array payload.');
        }

        return array_is_list($decoded) ? $decoded : [$decoded];
    }

    private function xmlRowTag(string $entity): string
    {
        return match ($entity) {
            'buildings' => 'building',
            'floors' => 'floor',
            'rooms' => 'room',
            'events' => 'event',
            'resources' => 'resource',
            'routes' => 'route',
            'schedules' => 'schedule',
            'courses' => 'course',
            'student_groups' => 'student_group',
            'students' => 'student',
            'allocation_parameters' => 'allocation_parameter',
            'exam_requests' => 'exam_request',
            'resource_requests' => 'resource_request',
            'online_sessions' => 'online_session',
            'movement_constraints' => 'movement_constraint',
            default => 'row',
        };
    }

    private function importExamRequest(array $row, ?int $userId): array
    {
        $row = $this->decodeJsonFields($row, ['required_resources']);

        $requestId = isset($row['id']) ? (int) $row['id'] : 0;
        if ($requestId > 0) {
            return $this->requests->update($requestId, $row, $userId);
        }

        return $this->requests->create($row, $userId);
    }

    private function importResourceRequest(array $row, ?int $userId): array
    {
        return $this->resources->importResourceRequest($row, $userId);
    }

    private function importOnlineSession(array $row, ?int $userId): array
    {
        return $this->events->importOnlineSession($row, $userId);
    }

    private function importMovementConstraint(array $row, ?int $userId): array
    {
        $row = $this->decodeJsonFields($row, ['rule_data']);

        $constraintId = isset($row['id']) ? (int) $row['id'] : 0;
        if ($constraintId > 0) {
            return $this->movementConstraints->update($constraintId, $row, $userId);
        }

        return $this->movementConstraints->create($row, $userId);
    }

    private function importSchedule(array $row, ?int $userId): array
    {
        $items = $this->scheduleItemsFromRow($row);
        unset($row['items'], $row['schedule_events'], $row['events_count']);

        $existing = $this->findExistingSchedule($row);

        $result = $existing === null
            ? $this->schedules->create($row, $userId)
            : $this->schedules->update((int) $existing->get('id'), $row, $userId);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        /** @var Schedule|null $schedule */
        $schedule = $result['schedule'] ?? null;
        if (!$schedule instanceof Schedule) {
            return ['success' => false, 'errors' => ['schedule' => ['Schedule record could not be loaded.']]];
        }

        if ($items !== []) {
            $this->schedules->replaceEvents((int) $schedule->get('id'), $items);
        }

        return ['success' => true, 'schedule' => $schedule];
    }

    private function importCourse(array $row, ?int $userId): array
    {
        $existing = !empty($row['code']) ? $this->courseRepository->findByCode((string) $row['code']) : null;

        return $existing === null
            ? $this->courses->create($row, $userId)
            : $this->courses->update((int) $existing->get('id'), $row, $userId);
    }

    private function importStudentGroup(array $row, ?int $userId): array
    {
        $existing = !empty($row['code']) ? $this->studentGroupRepository->findByCode((string) $row['code']) : null;

        return $existing === null
            ? $this->studentGroups->create($row, $userId)
            : $this->studentGroups->update((int) $existing->get('id'), $row, $userId);
    }

    private function importAllocationParameter(array $row, ?int $userId): array
    {
        $existing = !empty($row['code']) ? $this->allocationParameterRepository->findByCode((string) $row['code']) : null;

        return $existing === null
            ? $this->allocationParameters->create($row, $userId)
            : $this->allocationParameters->update((int) $existing->get('id'), $row, $userId);
    }

    private function importStudent(array $row, ?int $userId): array
    {
        $studentRoleId = $this->userRepository->studentRoleId();
        if ($studentRoleId === null) {
            return ['success' => false, 'errors' => ['student' => ['Student role is not configured.']]];
        }

        $payload = $row;
        $payload['role_id'] = $studentRoleId;
        $payload['status'] = $payload['status'] ?? 'active';

        $password = (string) ($payload['password'] ?? '');
        if ($password === '') {
            $seed = (string) ($payload['faculty_number'] ?? $payload['username'] ?? $payload['email'] ?? 'student');
            $payload['password'] = $seed;
        }

        $existing = null;
        if (!empty($payload['faculty_number'])) {
            $existing = $this->userRepository->findByFacultyNumber((string) $payload['faculty_number']);
        }
        if ($existing === null && !empty($payload['email'])) {
            $existing = $this->userRepository->findByEmail((string) $payload['email']);
        }
        if ($existing === null && !empty($payload['username'])) {
            $existing = $this->userRepository->findByLogin((string) $payload['username']);
        }

        $result = $existing === null
            ? $this->users->create($payload, $userId)
            : $this->users->update((int) $existing->get('id'), $payload, $userId);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        /** @var User|null $student */
        $student = $result['user'] ?? null;
        if (!$student instanceof User) {
            return ['success' => false, 'errors' => ['student' => ['Student record could not be loaded.']]];
        }

        $groupCodes = $this->groupCodesFromRow($row);
        if ($groupCodes !== []) {
            foreach ($groupCodes as $groupCode) {
                $group = $this->studentGroupRepository->findByCode($groupCode);
                if ($group !== null) {
                    $this->appendStudentToGroup((int) $group->get('id'), (int) $student->get('id'));
                }
            }
        }

        return ['success' => true, 'user' => $student];
    }

    private function appendStudentToGroup(int $groupId, int $userId): void
    {
        $members = $this->db()->fetchAll(
            'SELECT user_id FROM student_group_members WHERE student_group_id = :group_id',
            ['group_id' => $groupId]
        );
        $userIds = array_map(static fn (array $row): int => (int) $row['user_id'], $members);
        $userIds[] = $userId;
        $this->studentGroupRepository->syncMembers($groupId, $userIds);
    }

    private function groupCodesFromRow(array $row): array
    {
        $value = $row['group_codes'] ?? $row['groups'] ?? [];

        if (is_string($value)) {
            $parts = preg_split('/[,\|;]/', $value) ?: [];

            return array_values(array_filter(array_map(static fn (string $item): string => trim($item), $parts)));
        }

        return array_values(array_filter(array_map('strval', (array) $value)));
    }

    private function decodeJsonFields(array $row, array $fields): array
    {
        foreach ($fields as $field) {
            if (!isset($row[$field]) || !is_string($row[$field]) || trim($row[$field]) === '') {
                continue;
            }

            $decoded = json_decode($row[$field], true);
            if (is_array($decoded)) {
                $row[$field] = $decoded;
            }
        }

        return $row;
    }

    private function findExistingSchedule(array $row): ?Schedule
    {
        $scheduleId = isset($row['id']) ? (int) $row['id'] : 0;
        if ($scheduleId > 0) {
            $schedule = $this->scheduleRepository->find($scheduleId);
            if ($schedule !== null) {
                return $schedule;
            }
        }

        $name = trim((string) ($row['name'] ?? ''));
        $startsOn = trim((string) ($row['starts_on'] ?? ''));
        $endsOn = trim((string) ($row['ends_on'] ?? ''));

        if ($name === '' || $startsOn === '' || $endsOn === '') {
            return null;
        }

        foreach ($this->scheduleRepository->all() as $schedule) {
            if (
                (string) $schedule->get('name') === $name
                && (string) $schedule->get('starts_on') === $startsOn
                && (string) $schedule->get('ends_on') === $endsOn
            ) {
                return $schedule;
            }
        }

        return null;
    }

    private function scheduleItemsFromRow(array $row): array
    {
        $raw = $row['items'] ?? $row['schedule_events'] ?? [];

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        if (!$this->isList($raw)) {
            $raw = [$raw];
        }

        $items = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $eventId = (int) ($item['event_id'] ?? 0);
            if ($eventId <= 0) {
                continue;
            }

            $items[] = [
                'event_id' => $eventId,
                'assigned_room_id' => isset($item['assigned_room_id']) && $item['assigned_room_id'] !== '' ? (int) $item['assigned_room_id'] : null,
                'allocation_status' => (string) ($item['allocation_status'] ?? 'manual'),
                'conflict_notes' => isset($item['conflict_notes']) && $item['conflict_notes'] !== '' ? (string) $item['conflict_notes'] : null,
            ];
        }

        return $items;
    }

    private function isList(array $value): bool
    {
        return function_exists('array_is_list') ? array_is_list($value) : array_keys($value) === range(0, count($value) - 1);
    }

    private function summaryMessage(array $result): string
    {
        if (isset($result['floor_id'])) {
            return sprintf('Imported ASCII plan for floor %d.', (int) $result['floor_id']);
        }

        if (($result['entity'] ?? null) === 'buildings' || ($result['entity'] ?? null) === 'floors') {
            return sprintf(
                'Imported %d ASCII document(s), failed %d.',
                (int) ($result['imported'] ?? 0),
                (int) ($result['failed'] ?? 0)
            );
        }

        return sprintf(
            'Imported %d record(s), failed %d.',
            (int) ($result['imported'] ?? 0),
            (int) ($result['failed'] ?? 0)
        );
    }

    private function startJob(string $type, string $format, string $entity, ?string $filePath, ?int $userId): string
    {
        return $this->db()->insert(
            'INSERT INTO import_export_jobs (user_id, type, format, entity, file_path, status, message)
             VALUES (:user_id, :type, :format, :entity, :file_path, :status, :message)',
            [
                'user_id' => $userId,
                'type' => $type,
                'format' => $format,
                'entity' => $entity,
                'file_path' => $filePath,
                'status' => 'processing',
                'message' => null,
            ]
        );
    }

    private function finishJob(string $jobId, string $status, ?string $message): void
    {
        $this->db()->execute(
            'UPDATE import_export_jobs SET status = :status, message = :message, completed_at = :completed_at WHERE id = :id',
            [
                'id' => $jobId,
                'status' => $status,
                'message' => $message,
                'completed_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private function flattenErrors(array $errors): string
    {
        $messages = [];

        foreach ($errors as $field => $fieldErrors) {
            foreach ((array) $fieldErrors as $message) {
                $messages[] = is_string($field) ? "{$field}: {$message}" : (string) $message;
            }
        }

        return implode('; ', $messages);
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }

    private function importBuildingAsciiDocuments(string $content, ?int $userId): array
    {
        $documents = $this->parseAsciiBuildingDocuments($content);
        if ($documents === []) {
            throw new InvalidArgumentException('ASCII building file does not contain any BUILDING documents.');
        }

        $imported = 0;
        $errors = [];

        foreach ($documents as $index => $document) {
            try {
                $building = $this->upsertAsciiBuilding($document['building'], $userId);

                foreach ($document['floors'] as $floorDocument) {
                    $this->upsertAsciiFloor($floorDocument['meta'] + [
                        'building_id' => (int) $building->get('id'),
                        'building_code' => (string) $building->get('code'),
                    ], $floorDocument['plan'], $userId);
                }

                $imported++;
            } catch (Throwable $exception) {
                $errors[] = ['row' => $index + 1, 'message' => $exception->getMessage()];
            }
        }

        return [
            'entity' => 'buildings',
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    private function importFloorAsciiDocuments(string $content, ?int $userId, array $context): array
    {
        $trimmed = ltrim($content);
        $floorId = isset($context['floor_id']) ? (int) $context['floor_id'] : 0;

        if ($floorId > 0 && !str_starts_with($trimmed, 'FLOOR ')) {
            $result = $this->floors->savePlan($floorId, $content, [], null, $userId);
            if (!($result['success'] ?? false)) {
                throw new RuntimeException($this->flattenErrors((array) ($result['errors'] ?? [])));
            }

            return [
                'entity' => 'floors',
                'imported' => 1,
                'failed' => 0,
                'errors' => [],
                'floor_id' => $floorId,
                'floor' => $result['floor'] instanceof Floor ? $result['floor']->toArray() : null,
                'parsed_plan' => $result['parsed_plan'] ?? null,
                'note' => 'ASCII plan imported and persisted successfully.',
            ];
        }

        $documents = $this->parseAsciiFloorDocuments($content);
        if ($documents === []) {
            throw new InvalidArgumentException('ASCII floor file requires a target floor_id or FLOOR metadata headers.');
        }

        $imported = 0;
        $errors = [];
        $lastFloor = null;

        foreach ($documents as $index => $document) {
            try {
                $lastFloor = $this->upsertAsciiFloor($document['meta'], $document['plan'], $userId);
                $imported++;
            } catch (Throwable $exception) {
                $errors[] = ['row' => $index + 1, 'message' => $exception->getMessage()];
            }
        }

        return [
            'entity' => 'floors',
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => $errors,
            'floor_id' => $lastFloor?->get('id'),
            'floor' => $lastFloor?->toArray(),
        ];
    }

    private function parseAsciiBuildingDocuments(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $documents = [];
        $currentBuilding = null;
        $currentFloors = [];
        $currentFloorMeta = null;
        $currentPlanLines = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'BUILDING ')) {
                if ($currentBuilding !== null) {
                    $documents[] = ['building' => $currentBuilding, 'floors' => $currentFloors];
                }

                $currentBuilding = $this->decodeAsciiHeader(substr($line, 9), 'building');
                $currentFloors = [];
                $currentFloorMeta = null;
                $currentPlanLines = [];
                continue;
            }

            if (str_starts_with($line, 'FLOOR ')) {
                $currentFloorMeta = $this->decodeAsciiHeader(substr($line, 6), 'floor');
                $currentPlanLines = [];
                continue;
            }

            if (trim($line) === 'END_FLOOR') {
                if ($currentFloorMeta !== null) {
                    $currentFloors[] = [
                        'meta' => $currentFloorMeta,
                        'plan' => rtrim(implode(PHP_EOL, $currentPlanLines)),
                    ];
                }

                $currentFloorMeta = null;
                $currentPlanLines = [];
                continue;
            }

            if (trim($line) === 'END_BUILDING') {
                if ($currentBuilding !== null) {
                    $documents[] = ['building' => $currentBuilding, 'floors' => $currentFloors];
                }

                $currentBuilding = null;
                $currentFloors = [];
                $currentFloorMeta = null;
                $currentPlanLines = [];
                continue;
            }

            if ($currentFloorMeta !== null) {
                $currentPlanLines[] = $line;
            }
        }

        if ($currentBuilding !== null) {
            if ($currentFloorMeta !== null) {
                $currentFloors[] = [
                    'meta' => $currentFloorMeta,
                    'plan' => rtrim(implode(PHP_EOL, $currentPlanLines)),
                ];
            }

            $documents[] = ['building' => $currentBuilding, 'floors' => $currentFloors];
        }

        return $documents;
    }

    private function parseAsciiFloorDocuments(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $documents = [];
        $currentMeta = null;
        $currentPlanLines = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'FLOOR ')) {
                if ($currentMeta !== null) {
                    $documents[] = [
                        'meta' => $currentMeta,
                        'plan' => rtrim(implode(PHP_EOL, $currentPlanLines)),
                    ];
                }

                $currentMeta = $this->decodeAsciiHeader(substr($line, 6), 'floor');
                $currentPlanLines = [];
                continue;
            }

            if (trim($line) === 'END_FLOOR') {
                if ($currentMeta !== null) {
                    $documents[] = [
                        'meta' => $currentMeta,
                        'plan' => rtrim(implode(PHP_EOL, $currentPlanLines)),
                    ];
                }

                $currentMeta = null;
                $currentPlanLines = [];
                continue;
            }

            if ($currentMeta !== null) {
                $currentPlanLines[] = $line;
            }
        }

        if ($currentMeta !== null) {
            $documents[] = [
                'meta' => $currentMeta,
                'plan' => rtrim(implode(PHP_EOL, $currentPlanLines)),
            ];
        }

        return $documents;
    }

    private function decodeAsciiHeader(string $json, string $type): array
    {
        $decoded = json_decode(trim($json), true);

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(sprintf('Invalid %s ASCII header.', $type));
        }

        return $decoded;
    }

    private function upsertAsciiBuilding(array $meta, ?int $userId): Building
    {
        $buildingId = (int) ($meta['id'] ?? 0);
        $code = trim((string) ($meta['code'] ?? ''));
        $existing = $buildingId > 0 ? $this->buildingRepository->find($buildingId) : null;

        if ($existing === null && $code !== '') {
            $existing = $this->buildingRepository->findByCode($code);
        }

        $payload = array_filter([
            'code' => $code,
            'name' => trim((string) ($meta['name'] ?? '')),
            'orientation' => $meta['orientation'] ?? 'east',
            'address' => $meta['address'] ?? null,
            'description' => $meta['description'] ?? null,
            'status' => $meta['status'] ?? 'active',
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if ($existing === null) {
            if (($payload['code'] ?? '') === '' || ($payload['name'] ?? '') === '') {
                throw new InvalidArgumentException('ASCII building import requires at least building code and name.');
            }

            $result = $this->buildings->create($payload, $userId);
        } else {
            $result = $this->buildings->update((int) $existing->get('id'), $payload, $userId);
        }

        if (!($result['success'] ?? false) || !($result['building'] ?? null) instanceof Building) {
            throw new RuntimeException($this->flattenErrors((array) ($result['errors'] ?? [])));
        }

        return $result['building'];
    }

    private function upsertAsciiFloor(array $meta, string $plan, ?int $userId): Floor
    {
        $floor = null;
        $floorId = (int) ($meta['id'] ?? 0);

        if ($floorId > 0) {
            $floor = $this->floorRepository->find($floorId);
        }

        $buildingId = isset($meta['building_id']) ? (int) $meta['building_id'] : 0;
        if ($buildingId <= 0 && !empty($meta['building_code'])) {
            $building = $this->buildingRepository->findByCode((string) $meta['building_code']);
            $buildingId = $building === null ? 0 : (int) $building->get('id');
        }

        $number = isset($meta['number']) ? (int) $meta['number'] : null;

        if ($floor === null && $buildingId > 0 && $number !== null) {
            foreach ($this->floorRepository->byBuilding($buildingId) as $existingFloor) {
                if ((int) $existingFloor->get('number') === $number) {
                    $floor = $existingFloor;
                    break;
                }
            }
        }

        if ($floor === null) {
            if ($buildingId <= 0 || $number === null) {
                throw new InvalidArgumentException('ASCII floor import requires floor id or building + floor number metadata.');
            }

            $createResult = $this->floors->create([
                'building_id' => $buildingId,
                'number' => $number,
                'name' => trim((string) ($meta['name'] ?? ('Floor ' . $number))),
                'description' => $meta['description'] ?? null,
                'status' => $meta['status'] ?? 'active',
            ], $userId);

            if (!($createResult['success'] ?? false) || !($createResult['floor'] ?? null) instanceof Floor) {
                throw new RuntimeException($this->flattenErrors((array) ($createResult['errors'] ?? [])));
            }

            $floor = $createResult['floor'];
        } else {
            $updatePayload = array_filter([
                'building_id' => $buildingId > 0 ? $buildingId : null,
                'number' => $number,
                'name' => $meta['name'] ?? null,
                'description' => $meta['description'] ?? null,
                'status' => $meta['status'] ?? null,
            ], static fn (mixed $value): bool => $value !== null && $value !== '');

            if ($updatePayload !== []) {
                $updateResult = $this->floors->update((int) $floor->get('id'), $updatePayload, $userId);
                if (!($updateResult['success'] ?? false) || !($updateResult['floor'] ?? null) instanceof Floor) {
                    throw new RuntimeException($this->flattenErrors((array) ($updateResult['errors'] ?? [])));
                }

                $floor = $updateResult['floor'];
            }
        }

        $planResult = $this->floors->savePlan((int) $floor->get('id'), $plan, [], null, $userId);
        if (!($planResult['success'] ?? false) || !($planResult['floor'] ?? null) instanceof Floor) {
            throw new RuntimeException($this->flattenErrors((array) ($planResult['errors'] ?? [])));
        }

        return $planResult['floor'];
    }
}
