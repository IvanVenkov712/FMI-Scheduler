<?php

declare(strict_types=1);

class ScheduleRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?Schedule
    {
        return Schedule::find($id);
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM schedules WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= ' AND type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['semester'])) {
            $sql .= ' AND semester = :semester';
            $params['semester'] = $filters['semester'];
        }

        if (!empty($filters['active_on'])) {
            $sql .= ' AND starts_on <= :active_on AND ends_on >= :active_on';
            $params['active_on'] = $filters['active_on'];
        }

        $sql .= ' ORDER BY starts_on DESC, id DESC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Schedule => new Schedule($row), $rows);
    }

    public function create(array $data): Schedule
    {
        $schedule = new Schedule($data);
        $schedule->save();

        return $schedule;
    }

    public function update(int|string $id, array $data): ?Schedule
    {
        $schedule = $this->find($id);

        if ($schedule === null) {
            return null;
        }

        $schedule->fill($data);
        $schedule->save();

        return $schedule;
    }

    public function delete(int|string $id): bool
    {
        $schedule = $this->find($id);

        return $schedule?->delete() ?? false;
    }

    public function published(): array
    {
        return $this->all(['status' => 'published']);
    }

    public function activeOn(string $date): array
    {
        return $this->all(['active_on' => $date]);
    }

    public function calendar(?int $scheduleId = null): array
    {
        $params = [];
        $where = '';

        if ($scheduleId !== null) {
            $where = 'WHERE schedule_events.schedule_id = :schedule_id';
            $params['schedule_id'] = $scheduleId;
        }

        return $this->db()->fetchAll(
            'SELECT events.*, schedule_events.schedule_id, schedule_events.assigned_room_id,
                    schedule_events.allocation_status, schedule_events.conflict_notes,
                    rooms.code AS room_code, rooms.name AS room_name
             FROM schedule_events
             INNER JOIN events ON events.id = schedule_events.event_id
             LEFT JOIN rooms ON rooms.id = COALESCE(schedule_events.assigned_room_id, events.room_id)
             ' . $where . '
             ORDER BY events.starts_at ASC, events.id ASC',
            $params
        );
    }

    public function attachEvent(
        int $scheduleId,
        int $eventId,
        ?int $assignedRoomId = null,
        string $allocationStatus = 'manual',
        ?string $conflictNotes = null
    ): void {
        $this->db()->insert(
            'INSERT INTO schedule_events (schedule_id, event_id, assigned_room_id, allocation_status, conflict_notes)
             VALUES (:schedule_id, :event_id, :assigned_room_id, :allocation_status, :conflict_notes)
             ON DUPLICATE KEY UPDATE assigned_room_id = VALUES(assigned_room_id),
                                     allocation_status = VALUES(allocation_status),
                                     conflict_notes = VALUES(conflict_notes)',
            [
                'schedule_id' => $scheduleId,
                'event_id' => $eventId,
                'assigned_room_id' => $assignedRoomId,
                'allocation_status' => $allocationStatus,
                'conflict_notes' => $conflictNotes,
            ]
        );
    }

    public function detachEvent(int $scheduleId, int $eventId): int
    {
        return $this->db()->execute(
            'DELETE FROM schedule_events WHERE schedule_id = :schedule_id AND event_id = :event_id',
            ['schedule_id' => $scheduleId, 'event_id' => $eventId]
        );
    }

    public function eventsNeedingAllocation(int $scheduleId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT events.*
             FROM schedule_events
             INNER JOIN events ON events.id = schedule_events.event_id
             WHERE schedule_events.schedule_id = :schedule_id
               AND schedule_events.allocation_status IN ("unassigned", "conflict")
             ORDER BY events.starts_at ASC, events.students_count DESC',
            ['schedule_id' => $scheduleId]
        );

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
