<?php

declare(strict_types=1);

class Schedule extends Model
{
    protected string $table = 'schedules';
    protected array $fillable = [
        'name',
        'type',
        'academic_year',
        'semester',
        'starts_on',
        'ends_on',
        'status',
        'created_by',
    ];

    public function creator(): ?User
    {
        $createdBy = $this->get('created_by');

        return $createdBy === null ? null : User::find((int) $createdBy);
    }

    public function items(): array
    {
        if (!$this->exists()) {
            return [];
        }

        return $this->db()->fetchAll(
            'SELECT * FROM schedule_events WHERE schedule_id = :schedule_id ORDER BY id ASC',
            ['schedule_id' => $this->primaryValue()]
        );
    }

    public function events(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT events.*
             FROM schedule_events
             INNER JOIN events ON events.id = schedule_events.event_id
             WHERE schedule_events.schedule_id = :schedule_id
             ORDER BY events.starts_at ASC',
            ['schedule_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function isActiveOn(string $date): bool
    {
        $startsOn = strtotime((string) $this->get('starts_on', ''));
        $endsOn = strtotime((string) $this->get('ends_on', ''));
        $target = strtotime($date);

        if ($startsOn === false || $endsOn === false || $target === false) {
            return false;
        }

        return $target >= $startsOn && $target <= $endsOn;
    }

    public function isPublished(): bool
    {
        return $this->get('status', 'draft') === 'published';
    }

    public function attachEvent(int $eventId, ?int $assignedRoomId = null, string $allocationStatus = 'manual'): void
    {
        if (!$this->exists()) {
            throw new RuntimeException('Cannot attach event to an unsaved schedule.');
        }

        $this->db()->insert(
            'INSERT INTO schedule_events (schedule_id, event_id, assigned_room_id, allocation_status)
             VALUES (:schedule_id, :event_id, :assigned_room_id, :allocation_status)
             ON DUPLICATE KEY UPDATE assigned_room_id = VALUES(assigned_room_id), allocation_status = VALUES(allocation_status)',
            [
                'schedule_id' => $this->primaryValue(),
                'event_id' => $eventId,
                'assigned_room_id' => $assignedRoomId,
                'allocation_status' => $allocationStatus,
            ]
        );
    }
}
