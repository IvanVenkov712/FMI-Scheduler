<?php

declare(strict_types=1);

class ImportExportJobRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function recent(int $limit = 100): array
    {
        return $this->db()->fetchAll(
            'SELECT * FROM import_export_jobs ORDER BY created_at DESC LIMIT ' . max(1, $limit)
        );
    }

    public function start(string $type, string $format, string $entity, ?string $filePath, ?int $userId): string
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

    public function finish(string $jobId, string $status, ?string $message): void
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
