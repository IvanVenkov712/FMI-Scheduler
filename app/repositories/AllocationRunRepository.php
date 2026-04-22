<?php

declare(strict_types=1);

class AllocationRunRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function create(?int $scheduleId, array $input, ?int $userId): string
    {
        return $this->db()->insert(
            'INSERT INTO allocation_runs (schedule_id, requested_by, input_data, status, started_at)
             VALUES (:schedule_id, :requested_by, :input_data, :status, :started_at)',
            [
                'schedule_id' => $scheduleId,
                'requested_by' => $userId,
                'input_data' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public function finish(string $runId, array $result, string $status): void
    {
        $this->db()->execute(
            'UPDATE allocation_runs
             SET result_data = :result_data, status = :status, finished_at = :finished_at
             WHERE id = :id',
            [
                'id' => $runId,
                'result_data' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => $status,
                'finished_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public function find(int|string $id): ?array
    {
        return $this->db()->fetch(
            'SELECT * FROM allocation_runs WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
