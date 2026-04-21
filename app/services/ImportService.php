<?php

declare(strict_types=1);

class ImportService
{
    public function __construct(
        private readonly CsvParser $csv = new CsvParser(),
        private readonly XmlParser $xml = new XmlParser(),
        private readonly AsciiPlanParser $ascii = new AsciiPlanParser(),
        private readonly BuildingService $buildings = new BuildingService(),
        private readonly FloorService $floors = new FloorService(),
        private readonly RoomService $rooms = new RoomService(),
        private readonly EventService $events = new EventService(),
        private readonly ResourceService $resources = new ResourceService(),
        private readonly RouteService $routes = new RouteService(),
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

            $result = match ($entity) {
                'buildings' => $this->buildings->create($row, $userId),
                'floors' => $this->floors->create($row, $userId),
                'rooms' => $this->rooms->create($row, [], $userId),
                'events' => $this->events->create($row, null, $userId),
                'resources' => $this->resources->create($row, $userId),
                'routes' => $this->routes->create($row, [], $userId),
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
        if ($entity !== 'floors') {
            throw new InvalidArgumentException('ASCII import is supported only for floor plans.');
        }

        $floorId = isset($context['floor_id']) ? (int) $context['floor_id'] : 0;
        if ($floorId <= 0) {
            throw new InvalidArgumentException('ASCII import requires a target floor_id.');
        }

        $result = $this->floors->importAsciiPlan($floorId, $filePath, $userId);
        if (!($result['success'] ?? false)) {
            throw new RuntimeException($this->flattenErrors((array) ($result['errors'] ?? [])));
        }

        return [
            'imported' => 1,
            'failed' => 0,
            'errors' => [],
            'floor_id' => $floorId,
            'floor' => $result['floor'] instanceof Floor ? $result['floor']->toArray() : null,
            'parsed_plan' => $result['parsed_plan'] ?? null,
            'note' => 'ASCII plan imported and persisted successfully.',
        ];
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
            default => 'row',
        };
    }

    private function summaryMessage(array $result): string
    {
        if (isset($result['floor_id'])) {
            return sprintf('Imported ASCII plan for floor %d.', (int) $result['floor_id']);
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
}
