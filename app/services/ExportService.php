<?php

declare(strict_types=1);

class ExportService
{
    public function __construct(
        private readonly CsvParser $csv = new CsvParser(),
        private readonly XmlDataParser $xml = new XmlDataParser(),
        private readonly AsciiPlanParser $ascii = new AsciiPlanParser(),
        private readonly BuildingRepository $buildings = new BuildingRepository(),
        private readonly FloorRepository $floors = new FloorRepository(),
        private readonly RoomRepository $rooms = new RoomRepository(),
        private readonly EventRepository $events = new EventRepository(),
        private readonly ResourceRepository $resources = new ResourceRepository(),
        private readonly RouteRepository $routes = new RouteRepository(),
        private readonly ScheduleRepository $schedules = new ScheduleRepository(),
        private readonly CourseRepository $courses = new CourseRepository(),
        private readonly StudentGroupRepository $studentGroups = new StudentGroupRepository(),
        private readonly AllocationParameterRepository $allocationParameters = new AllocationParameterRepository(),
        private readonly RequestRepository $requests = new RequestRepository(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly ?Database $database = null
    ) {
    }

    public function exportEntity(string $entity, string $format, ?int $entityId = null, ?string $targetPath = null, ?int $userId = null): array
    {
        $jobId = $this->startJob('export', $format, $entity, $targetPath, $userId);

        try {
            $rows = $this->rowsForEntity($entity, $entityId);
            if ($format === 'csv') {
                $rows = $this->flattenRowsForCsv($rows);
            }

            $content = match ($format) {
                'csv' => $this->csv->toString($rows),
                'xml' => $this->xml->toString($rows, $entity, rtrim($entity, 's')),
                'json' => json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]',
                'txt' => $this->toText($entity, $rows),
                'ascii' => $this->toAscii($entity, $entityId, $rows),
                default => throw new InvalidArgumentException('Unsupported export format.'),
            };

            if ($targetPath !== null && $targetPath !== '') {
                file_put_contents($targetPath, $content);
            }

            $this->finishJob($jobId, 'completed', 'Export finished successfully.');

            return [
                'success' => true,
                'job_id' => $jobId,
                'content' => $content,
                'rows' => count($rows),
                'path' => $targetPath,
            ];
        } catch (Throwable $exception) {
            $this->finishJob($jobId, 'failed', $exception->getMessage());

            return ['success' => false, 'job_id' => $jobId, 'errors' => ['export' => [$exception->getMessage()]]];
        }
    }

    public function exportScheduleText(int $scheduleId): string
    {
        $schedule = $this->schedules->find($scheduleId);
        if ($schedule === null) {
            throw new RuntimeException('Schedule not found.');
        }

        $lines = [
            'Schedule: ' . $schedule->get('name'),
            'Type: ' . $schedule->get('type'),
            'Period: ' . $schedule->get('starts_on') . ' - ' . $schedule->get('ends_on'),
            'Status: ' . $schedule->get('status'),
            '',
        ];

        foreach ($this->schedules->calendar($scheduleId) as $event) {
            $lines[] = sprintf(
                '[%s - %s] %s | room: %s | mode: %s | status: %s',
                $event['starts_at'],
                $event['ends_at'],
                $event['title'],
                $event['room_code'] ?? '-',
                $event['delivery_mode'] ?? 'physical',
                $event['allocation_status'] ?? $event['status']
            );
        }

        return implode(PHP_EOL, $lines);
    }

    public function exportFloorAscii(int $floorId): string
    {
        $floor = $this->floors->find($floorId);
        if ($floor === null) {
            throw new RuntimeException('Floor not found.');
        }

        return $this->serializeFloorAsciiDocument($floor);
    }

    private function rowsForEntity(string $entity, ?int $entityId): array
    {
        return match ($entity) {
            'buildings' => $this->mapModels($entityId === null ? $this->buildings->all() : array_filter([$this->buildings->find($entityId)])),
            'floors' => $this->mapModels($entityId === null ? Floor::all('building_id', 'ASC') : array_filter([$this->floors->find($entityId)])),
            'rooms' => $this->mapModels($entityId === null ? Room::all('code', 'ASC') : array_filter([$this->rooms->find($entityId)])),
            'events' => $this->mapModels($entityId === null ? $this->events->all() : array_filter([$this->events->find($entityId)])),
            'resources' => $this->mapModels($entityId === null ? $this->resources->all() : array_filter([$this->resources->find($entityId)])),
            'routes' => $this->mapModels($entityId === null ? $this->routes->all() : array_filter([$this->routes->find($entityId)])),
            'schedules' => $this->scheduleRows($entityId),
            'courses' => $this->mapModels($entityId === null ? $this->courses->all() : array_filter([$this->courses->find($entityId)])),
            'student_groups' => $this->mapRows($entityId === null ? $this->studentGroups->exportRows() : $this->filterById($this->studentGroups->exportRows(), $entityId)),
            'students' => $this->mapRows($entityId === null ? $this->users->allStudents() : $this->filterById($this->users->allStudents(), $entityId)),
            'allocation_parameters' => $this->mapRows($entityId === null ? $this->allocationParameters->exportRows() : $this->filterById($this->allocationParameters->exportRows(), $entityId)),
            'exam_requests' => $this->examRequestRows($entityId),
            'resource_requests' => $this->resourceRequestRows($entityId),
            'online_sessions' => $this->mapModels($entityId === null ? OnlineSession::all('event_id', 'ASC') : array_filter([$this->findOnlineSessionById($entityId)])),
            'movement_constraints' => $this->movementConstraintRows($entityId),
            default => throw new InvalidArgumentException('Unsupported export entity.'),
        };
    }

    private function examRequestRows(?int $entityId): array
    {
        $requests = $entityId === null ? $this->requests->all() : array_filter([$this->requests->find($entityId)]);

        return array_values(array_map(static function (ExamRequest $request): array {
            $row = SensitiveData::strip($request->toArray());
            $row['required_resources'] = $request->requiredResources();

            return $row;
        }, array_filter($requests)));
    }

    private function resourceRequestRows(?int $entityId): array
    {
        $rows = $entityId === null
            ? $this->resources->resourceRequests()
            : array_filter([$this->resources->findResourceRequest($entityId)]);

        return $this->mapRows(array_values($rows));
    }

    private function movementConstraintRows(?int $entityId): array
    {
        $constraints = $entityId === null
            ? MovementConstraint::all('id', 'ASC')
            : array_filter([MovementConstraint::find($entityId)]);

        return array_values(array_map(static function (MovementConstraint $constraint): array {
            $row = SensitiveData::strip($constraint->toArray());
            $row['rule_data'] = $constraint->ruleData();

            return $row;
        }, array_filter($constraints)));
    }

    private function findOnlineSessionById(int $entityId): ?OnlineSession
    {
        return OnlineSession::find($entityId);
    }

    private function scheduleRows(?int $entityId): array
    {
        $schedules = $entityId === null ? $this->schedules->all() : array_filter([$this->schedules->find($entityId)]);

        return array_values(array_map(function (Schedule $schedule): array {
            $row = SensitiveData::strip($schedule->toArray());
            $row['items'] = array_values(array_map(static function (array $item): array {
                return [
                    'event_id' => (int) ($item['event_id'] ?? 0),
                    'assigned_room_id' => isset($item['assigned_room_id']) && $item['assigned_room_id'] !== '' ? (int) $item['assigned_room_id'] : null,
                    'allocation_status' => (string) ($item['allocation_status'] ?? 'manual'),
                    'conflict_notes' => $item['conflict_notes'] ?? null,
                ];
            }, $schedule->items()));
            $row['events_count'] = count($row['items']);

            return $row;
        }, array_filter($schedules)));
    }

    private function mapModels(array $models): array
    {
        return array_values(array_map(
            static fn (object $model): array => SensitiveData::strip(method_exists($model, 'toArray') ? $model->toArray() : (array) $model),
            array_filter($models)
        ));
    }

    private function mapRows(array $rows): array
    {
        return array_values(array_map(static fn (array $row): array => SensitiveData::strip($row), $rows));
    }

    private function filterById(array $rows, int $entityId): array
    {
        return array_values(array_filter($rows, static fn (array $row): bool => (int) ($row['id'] ?? 0) === $entityId));
    }

    private function flattenRowsForCsv(array $rows): array
    {
        return array_values(array_map(function (array $row): array {
            foreach ($row as $key => $value) {
                if (is_array($value)) {
                    $row[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            return $row;
        }, $rows));
    }

    private function toText(string $entity, array $rows): string
    {
        $lines = [strtoupper($entity)];

        foreach ($rows as $index => $row) {
            $lines[] = '';
            $lines[] = '# ' . ($index + 1);

            foreach ($row as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $lines[] = sprintf('%s: %s', $key, (string) $value);
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function toAscii(string $entity, ?int $entityId, array $rows): string
    {
        if ($entity === 'buildings') {
            return $this->exportBuildingsAscii($entityId, $rows);
        }

        if ($entity === 'floors') {
            return $this->exportFloorsAscii($entityId, $rows);
        }

        return $this->toText($entity, $rows);
    }

    private function exportBuildingsAscii(?int $entityId, array $rows): string
    {
        $buildingIds = $entityId !== null
            ? [$entityId]
            : array_values(array_filter(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows)));

        $documents = [];

        foreach ($buildingIds as $buildingId) {
            $building = $this->buildings->find($buildingId);
            if ($building === null) {
                continue;
            }

            $documents[] = $this->serializeBuildingAsciiDocument($building);
        }

        return $documents === [] ? $this->toText('buildings', $rows) : implode(PHP_EOL . PHP_EOL, $documents);
    }

    private function exportFloorsAscii(?int $entityId, array $rows): string
    {
        if ($entityId !== null) {
            return $this->exportFloorAscii($entityId);
        }

        $documents = [];

        foreach ($rows as $row) {
            $floorId = (int) ($row['id'] ?? 0);
            $floor = $floorId > 0 ? $this->floors->find($floorId) : null;

            if ($floor === null) {
                continue;
            }

            $documents[] = $this->serializeFloorAsciiDocument($floor);
        }

        return $documents === [] ? $this->toText('floors', $rows) : implode(PHP_EOL . PHP_EOL, $documents);
    }

    private function serializeBuildingAsciiDocument(Building $building): string
    {
        $header = [
            'id' => (int) $building->get('id'),
            'code' => (string) $building->get('code'),
            'name' => (string) $building->get('name'),
            'orientation' => (string) $building->get('orientation', 'east'),
            'address' => $building->get('address'),
            'description' => $building->get('description'),
            'status' => (string) $building->get('status', 'active'),
        ];

        $parts = [
            'BUILDING ' . json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        foreach ($building->floors() as $floor) {
            $parts[] = $this->serializeFloorAsciiDocument($floor);
        }

        $parts[] = 'END_BUILDING';

        return implode(PHP_EOL . PHP_EOL, $parts);
    }

    private function serializeFloorAsciiDocument(Floor $floor): string
    {
        $building = $floor->building();
        $header = [
            'id' => (int) $floor->get('id'),
            'building_id' => (int) $floor->get('building_id'),
            'building_code' => $building?->get('code'),
            'number' => (int) $floor->get('number'),
            'name' => (string) $floor->get('name'),
            'description' => $floor->get('description'),
            'status' => (string) $floor->get('status', 'active'),
            'width_units' => $floor->get('width_units'),
            'height_units' => $floor->get('height_units'),
        ];

        $parts = ['FLOOR ' . json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
        $plan = trim((string) $floor->get('ascii_plan', ''));

        if ($plan !== '') {
            $parts[] = $plan;
        }

        $parts[] = 'END_FLOOR';

        return implode(PHP_EOL, $parts);
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

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
