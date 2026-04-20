<?php

declare(strict_types=1);

class DashboardController extends Controller
{
    public function index(): string
    {
        return $this->render('dashboard/index', [
            'stats' => $this->stats(),
            'upcoming_events' => $this->upcomingEvents(),
            'pending_requests' => $this->pendingRequests(),
        ]);
    }

    private function stats(): array
    {
        $tables = ['buildings', 'floors', 'rooms', 'events', 'schedules', 'exam_requests', 'routes'];
        $stats = [];

        foreach ($tables as $table) {
            $stats[$table] = (int) ($this->database()->fetch("SELECT COUNT(*) AS count FROM {$table}")['count'] ?? 0);
        }

        return $stats;
    }

    private function upcomingEvents(): array
    {
        return $this->database()->fetchAll(
            'SELECT events.*, rooms.code AS room_code
             FROM events
             LEFT JOIN rooms ON rooms.id = events.room_id
             WHERE events.starts_at >= NOW()
             ORDER BY events.starts_at ASC
             LIMIT 10'
        );
    }

    private function pendingRequests(): array
    {
        return $this->database()->fetchAll(
            'SELECT * FROM exam_requests WHERE status = :status ORDER BY created_at DESC LIMIT 10',
            ['status' => 'pending']
        );
    }
}
