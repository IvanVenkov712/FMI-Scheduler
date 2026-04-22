<?php

declare(strict_types=1);

class ImportExportController extends Controller
{
    private array $importEntities = ['buildings', 'floors', 'rooms', 'events', 'resources', 'routes', 'schedules', 'courses', 'student_groups', 'students', 'allocation_parameters', 'exam_requests', 'resource_requests', 'online_sessions', 'movement_constraints'];
    private array $exportEntities = ['buildings', 'floors', 'rooms', 'events', 'resources', 'routes', 'schedules', 'courses', 'student_groups', 'students', 'allocation_parameters', 'exam_requests', 'resource_requests', 'online_sessions', 'movement_constraints'];

    public function import(): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->render('import-export/import', ['entities' => $this->importEntities]);
        }

        $entity = (string) $this->input('entity', '');
        $format = (string) $this->input('format', 'csv');

        if (!in_array($entity, $this->importEntities, true)) {
            return $this->fail('Unsupported import entity.', 422);
        }

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            return $this->fail('Import file is required.', 422);
        }

        if (in_array(strtolower($format), ['ascii', 'txt'], true) && $entity === 'floors' && $this->importContext($entity) === []) {
            return $this->fail('ASCII import for floors requires a target floor_id.', 422);
        }

        try {
            $upload = $this->uploader()->upload(
                $_FILES['file'],
                $this->importsDirectory(),
                $this->allowedImportExtensions(),
                $this->maxUploadSize()
            );

            $result = $this->importService()->importFile(
                $entity,
                $format,
                $upload['path'],
                $this->currentUserId(),
                $this->importContext($entity)
            );

            if (!($result['success'] ?? false)) {
                return $this->fail($this->resultMessage($result, 'Import failed.'), 422, $result);
            }

            return $this->success($result, $this->resultMessage($result, 'Import finished successfully.'));
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception->getMessage(), 422);
        } catch (Throwable $exception) {
            return $this->fail($exception->getMessage(), 500);
        }
    }

    public function export(): mixed
    {
        $entity = (string) $this->input('entity', 'rooms');
        $format = (string) $this->input('format', 'csv');

        if (!in_array($entity, $this->exportEntities, true)) {
            return $this->fail('Unsupported export entity.', 422);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $this->input('download') === null) {
            return $this->render('import-export/export', ['entities' => $this->exportEntities]);
        }

        try {
            $entityId = $this->normalizeEntityId($this->input('entity_id'));
            $targetPath = $this->exportTargetPath($entity, $format);

            $result = $this->exportService()->exportEntity(
                $entity,
                $format,
                $entityId,
                $targetPath,
                $this->currentUserId()
            );

            if (!($result['success'] ?? false)) {
                return $this->fail($this->resultMessage($result, 'Export failed.'), 422, $result);
            }

            if (!$this->wantsJson() && $this->input('download') !== null && is_string($result['path'] ?? null)) {
                Response::download(
                    $result['path'],
                    basename($result['path']),
                    $this->contentTypeForFormat($format)
                );
            }

            return $this->success($result, $this->resultMessage($result, 'Export finished successfully.'));
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception->getMessage(), 422);
        } catch (Throwable $exception) {
            return $this->fail($exception->getMessage(), 500);
        }
    }

    public function history(): string
    {
        return $this->render('import-export/history', [
            'jobs' => $this->database()->fetchAll('SELECT * FROM import_export_jobs ORDER BY created_at DESC LIMIT 100'),
        ]);
    }

    private function success(array $data, string $message): mixed
    {
        if ($this->wantsJson()) {
            return [
                'message' => $message,
                'result' => $data,
            ];
        }

        return $this->render('import-export/history', [
            'message' => $message,
            'result' => $data,
            'jobs' => $this->database()->fetchAll('SELECT * FROM import_export_jobs ORDER BY created_at DESC LIMIT 100'),
        ]);
    }

    private function fail(string $message, int $status, array $result = []): mixed
    {
        http_response_code($status);

        if ($this->wantsJson()) {
            return ['error' => $message, 'result' => $result];
        }

        return $this->render('import-export/import', [
            'error' => $message,
            'entities' => $this->importEntities,
            'result' => $result,
        ]);
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || $this->input('format') === 'json';
    }

    private function currentUserId(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    private function importService(): ImportService
    {
        return new ImportService();
    }

    private function exportService(): ExportService
    {
        return new ExportService();
    }

    private function uploader(): FileUploader
    {
        return new FileUploader();
    }

    private function appConfig(): array
    {
        static $config = null;

        if (is_array($config)) {
            return $config;
        }

        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        $loaded = is_file($path) ? require $path : [];
        $config = is_array($loaded) ? $loaded : [];

        return $config;
    }

    private function importsDirectory(): string
    {
        $config = $this->appConfig();

        return (string) ($config['paths']['imports'] ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'imports');
    }

    private function exportsDirectory(): string
    {
        $config = $this->appConfig();

        return (string) ($config['paths']['exports'] ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'exports');
    }

    private function allowedImportExtensions(): array
    {
        $config = $this->appConfig();

        return (array) ($config['uploads']['allowed_import_extensions'] ?? ['csv', 'json', 'xml', 'txt', 'ascii']);
    }

    private function maxUploadSize(): int
    {
        $config = $this->appConfig();

        return (int) ($config['uploads']['max_file_size'] ?? 5 * 1024 * 1024);
    }

    private function exportTargetPath(string $entity, string $format): string
    {
        $directory = $this->exportsDirectory();

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Export directory could not be created.');
        }

        return rtrim($directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $entity
            . '-'
            . date('Ymd-His')
            . '.'
            . $format;
    }

    private function normalizeEntityId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value > 0 ? (int) $value : null;
    }

    private function resultMessage(array $result, string $fallback): string
    {
        if (!empty($result['errors']) && is_array($result['errors'])) {
            $messages = [];

            foreach ($result['errors'] as $error) {
                if (is_array($error) && isset($error['message'])) {
                    $messages[] = (string) $error['message'];
                } elseif (is_string($error)) {
                    $messages[] = $error;
                }
            }

            if ($messages !== []) {
                return implode('; ', $messages);
            }
        }

        if (isset($result['imported'])) {
            return sprintf(
                'Imported %d record(s), failed %d.',
                (int) $result['imported'],
                (int) ($result['failed'] ?? 0)
            );
        }

        if (isset($result['rows'])) {
            return sprintf('Exported %d record(s).', (int) $result['rows']);
        }

        return $fallback;
    }

    private function contentTypeForFormat(string $format): string
    {
        return match (strtolower($format)) {
            'csv' => 'text/csv; charset=utf-8',
            'xml' => 'application/xml; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'txt', 'ascii' => 'text/plain; charset=utf-8',
            default => 'application/octet-stream',
        };
    }

    private function importContext(string $entity): array
    {
        if ($entity !== 'floors') {
            return [];
        }

        $floorId = $this->normalizeEntityId($this->input('floor_id') ?? $this->input('entity_id'));

        return $floorId === null ? [] : ['floor_id' => $floorId];
    }
}
