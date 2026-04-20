<?php

declare(strict_types=1);

class ImportExportController extends Controller
{
    private array $entities = [
        'buildings' => ['code', 'name', 'address', 'description', 'floors_count', 'orientation', 'schematic_description', 'status'],
        'floors' => ['building_id', 'number', 'name', 'description', 'plan_data', 'ascii_plan', 'capacity', 'width_units', 'height_units', 'status'],
        'rooms' => ['floor_id', 'room_type_id', 'code', 'name', 'description', 'capacity', 'covid_capacity', 'x', 'y', 'width', 'height', 'online_url', 'access_key', 'status'],
        'events' => ['created_by', 'room_id', 'title', 'type', 'description', 'course_name', 'group_name', 'students_count', 'starts_at', 'ends_at', 'delivery_mode', 'status'],
        'routes' => ['from_room_id', 'to_room_id', 'from_building_id', 'to_building_id', 'name', 'description', 'movement_type', 'distance_meters', 'duration_minutes', 'path_data', 'is_accessible', 'status'],
    ];

    public function import(): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->render('import-export/import', ['entities' => array_keys($this->entities)]);
        }

        $entity = (string) $this->input('entity', '');
        $format = (string) $this->input('format', 'csv');

        if (!isset($this->entities[$entity])) {
            return $this->fail('Unsupported import entity.', 422);
        }

        $path = $_FILES['file']['tmp_name'] ?? null;
        if (!is_string($path) || !is_uploaded_file($path)) {
            return $this->fail('Import file is required.', 422);
        }

        $jobId = $this->createJob('import', $format, $entity, $_FILES['file']['name'] ?? null);

        try {
            $rows = $this->readRows($path, $format);
            $count = $this->insertRows($entity, $rows);
            $this->completeJob($jobId, "Imported {$count} rows.");

            return $this->success(['job_id' => $jobId, 'imported' => $count]);
        } catch (Throwable $exception) {
            $this->failJob($jobId, $exception->getMessage());
            return $this->fail($exception->getMessage(), 500);
        }
    }

    public function export(): mixed
    {
        $entity = (string) $this->input('entity', 'rooms');
        $format = (string) $this->input('format', 'csv');

        if (!isset($this->entities[$entity])) {
            return $this->fail('Unsupported export entity.', 422);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $this->input('download') === null) {
            return $this->render('import-export/export', ['entities' => array_keys($this->entities)]);
        }

        $rows = $this->database()->fetchAll('SELECT * FROM ' . $entity . ' ORDER BY id ASC');
        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'exports';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $fileName = $entity . '-' . date('Ymd-His') . '.' . $format;
        $path = $directory . DIRECTORY_SEPARATOR . $fileName;
        $this->writeRows($path, $rows, $format);

        $jobId = $this->createJob('export', $format, $entity, $path);
        $this->completeJob($jobId, 'Export completed.');

        return $this->success(['job_id' => $jobId, 'file' => $path, 'rows' => count($rows)]);
    }

    public function history(): string
    {
        return $this->render('import-export/history', [
            'jobs' => $this->database()->fetchAll('SELECT * FROM import_export_jobs ORDER BY created_at DESC LIMIT 100'),
        ]);
    }

    private function readRows(string $path, string $format): array
    {
        if ($format === 'json') {
            $rows = json_decode((string) file_get_contents($path), true);
            return is_array($rows) ? $rows : [];
        }

        if ($format === 'xml') {
            $xml = simplexml_load_file($path);
            if ($xml === false) {
                throw new RuntimeException('Invalid XML import file.');
            }

            $rows = [];
            foreach ($xml->row as $row) {
                $rows[] = json_decode(json_encode($row, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            }

            return $rows;
        }

        if ($format === 'txt' || $format === 'ascii') {
            return [['ascii_plan' => file_get_contents($path)]];
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Cannot read import file.');
        }

        $headers = fgetcsv($handle) ?: [];
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($headers, $row) ?: [];
        }

        fclose($handle);

        return $rows;
    }

    private function insertRows(string $entity, array $rows): int
    {
        $count = 0;
        $columns = $this->entities[$entity];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $data = array_intersect_key($row, array_flip($columns));
            if ($data === []) {
                continue;
            }

            $names = array_keys($data);
            $placeholders = array_map(static fn (string $name): string => ':' . $name, $names);
            $this->database()->insert(
                sprintf('INSERT INTO %s (%s) VALUES (%s)', $entity, implode(', ', $names), implode(', ', $placeholders)),
                $data
            );
            $count++;
        }

        return $count;
    }

    private function writeRows(string $path, array $rows, string $format): void
    {
        if ($format === 'json') {
            file_put_contents($path, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }

        if ($format === 'xml') {
            $xml = new SimpleXMLElement('<rows/>');

            foreach ($rows as $row) {
                $item = $xml->addChild('row');

                foreach ($row as $key => $value) {
                    $item->addChild((string) $key, htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                }
            }

            $xml->asXML($path);
            return;
        }

        if ($format === 'txt' || $format === 'ascii') {
            $lines = array_map(
                static fn (array $row): string => $row['ascii_plan'] ?? json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $rows
            );
            file_put_contents($path, implode(PHP_EOL . PHP_EOL, $lines));
            return;
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Cannot create export file.');
        }

        if ($rows !== []) {
            fputcsv($handle, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
        }

        fclose($handle);
    }

    private function createJob(string $type, string $format, string $entity, ?string $filePath): string
    {
        return $this->database()->insert(
            'INSERT INTO import_export_jobs (user_id, type, format, entity, file_path, status)
             VALUES (:user_id, :type, :format, :entity, :file_path, "processing")',
            [
                'user_id' => $_SESSION['user']['id'] ?? null,
                'type' => $type,
                'format' => $format,
                'entity' => $entity,
                'file_path' => $filePath,
            ]
        );
    }

    private function completeJob(int|string $id, string $message): void
    {
        $this->database()->execute(
            'UPDATE import_export_jobs SET status = "completed", message = :message, completed_at = NOW() WHERE id = :id',
            ['id' => $id, 'message' => $message]
        );
    }

    private function failJob(int|string $id, string $message): void
    {
        $this->database()->execute(
            'UPDATE import_export_jobs SET status = "failed", message = :message, completed_at = NOW() WHERE id = :id',
            ['id' => $id, 'message' => $message]
        );
    }

    private function success(array $data): mixed
    {
        return $this->wantsJson() ? $data : $this->render('import-export/history', $data);
    }

    private function fail(string $message, int $status): mixed
    {
        http_response_code($status);
        return $this->wantsJson() ? ['error' => $message] : $this->render('import-export/import', ['error' => $message]);
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || $this->input('format') === 'json';
    }
}
