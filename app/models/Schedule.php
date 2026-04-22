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

        return $createdBy === null ? null : (new UserRepository())->find((int) $createdBy);
    }

    public function items(): array
    {
        return $this->exists() ? (new ScheduleRepository())->items((int) $this->primaryValue()) : [];
    }

    public function events(): array
    {
        return $this->exists() ? (new EventRepository())->forSchedule((int) $this->primaryValue()) : [];
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

        (new ScheduleRepository())->attachEvent((int) $this->primaryValue(), $eventId, $assignedRoomId, $allocationStatus);
    }
}
