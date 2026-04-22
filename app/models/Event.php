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
        'required_resources',
        'status',
    ];

    public function creator(): ?User
    {
        $createdBy = $this->get('created_by');

        return $createdBy === null ? null : (new UserRepository())->find((int) $createdBy);
    }

    public function room(): ?Room
    {
        $roomId = $this->get('room_id');

        return $roomId === null ? null : (new RoomRepository())->find((int) $roomId);
    }

    public function onlineSession(): ?OnlineSession
    {
        return $this->exists() ? (new OnlineSessionRepository())->findByEventId((int) $this->primaryValue()) : null;
    }

    public function scheduleLinks(): array
    {
        return $this->exists() ? (new EventRepository())->scheduleLinks((int) $this->primaryValue()) : [];
    }

    public function requiredResources(): array
    {
        $value = $this->get('required_resources');

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
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
        return (new EventRepository())->upcoming($limit);
    }
}
