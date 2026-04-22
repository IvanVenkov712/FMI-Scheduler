<?php

declare(strict_types=1);

class DashboardRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function stats(): array
    {
        $tables = ['buildings', 'floors', 'rooms', 'events', 'schedules', 'exam_requests', 'routes'];
        $stats = [];

        foreach ($tables as $table) {
            $stats[$table] = (int) ($this->db()->fetch("SELECT COUNT(*) AS count FROM {$table}")['count'] ?? 0);
        }

        return $stats;
    }

    public function upcomingEvents(int $limit = 10): array
    {
        return $this->db()->fetchAll(
            'SELECT events.*, rooms.code AS room_code
             FROM events
             LEFT JOIN rooms ON rooms.id = events.room_id
             WHERE events.starts_at >= NOW()
             ORDER BY events.starts_at ASC
             LIMIT ' . max(1, $limit)
        );
    }

    public function pendingRequests(int $limit = 10): array
    {
        return $this->db()->fetchAll(
            'SELECT * FROM exam_requests WHERE status = :status ORDER BY created_at DESC LIMIT ' . max(1, $limit),
            ['status' => 'pending']
        );
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
