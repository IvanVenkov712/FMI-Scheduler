<?php

declare(strict_types=1);

class Event extends Model
{
    protected string $table = 'events';
    protected array $fillable = [
        'created_by',
        'room_id',
        'title',
        'type',
        'description',
        'course_name',
        'group_name',
        'students_count',
        'starts_at',
        'ends_at',
        'delivery_mode',
        'status',
    ];

    public function creator(): ?User
    {
        $createdBy = $this->get('created_by');

        return $createdBy === null ? null : User::find((int) $createdBy);
    }

    public function room(): ?Room
    {
        $roomId = $this->get('room_id');

        return $roomId === null ? null : Room::find((int) $roomId);
    }

    public function onlineSession(): ?OnlineSession
    {
        if (!$this->exists()) {
            return null;
        }

        $row = $this->db()->fetch(
            'SELECT * FROM online_sessions WHERE event_id = :event_id LIMIT 1',
            ['event_id' => $this->primaryValue()]
        );

        return $row === null ? null : new OnlineSession($row);
    }

    public function scheduleLinks(): array
    {
        if (!$this->exists()) {
            return [];
        }

        return $this->db()->fetchAll(
            'SELECT * FROM schedule_events WHERE event_id = :event_id ORDER BY id ASC',
            ['event_id' => $this->primaryValue()]
        );
    }

    public function overlaps(string $startsAt, string $endsAt): bool
    {
        $currentStart = strtotime((string) $this->get('starts_at', ''));
        $currentEnd = strtotime((string) $this->get('ends_at', ''));
        $targetStart = strtotime($startsAt);
        $targetEnd = strtotime($endsAt);

        if ($currentStart === false || $currentEnd === false || $targetStart === false || $targetEnd === false) {
            return false;
        }

        return $currentStart < $targetEnd && $currentEnd > $targetStart;
    }

    public function isScheduled(): bool
    {
        return in_array((string) $this->get('status'), ['scheduled', 'completed'], true);
    }

    public function requiresOnlineSession(): bool
    {
        return in_array((string) $this->get('delivery_mode', 'physical'), ['online', 'hybrid'], true);
    }

    public static function upcoming(int $limit = 10): array
    {
        $rows = Database::getInstance(Database::configFromFile())->fetchAll(
            'SELECT * FROM events WHERE starts_at >= NOW() ORDER BY starts_at ASC LIMIT ' . max(1, $limit)
        );

        return array_map(static fn (array $row): self => new self($row), $rows);
    }
}
