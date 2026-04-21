<?php

declare(strict_types=1);

class ExamRequest extends Model
{
    protected string $table = 'exam_requests';
    protected array $fillable = [
        'requested_by',
        'event_id',
        'course_name',
        'students_count',
        'preferred_room_id',
        'preferred_delivery_mode',
        'preferred_starts_at',
        'preferred_ends_at',
        'required_resources',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    public function requester(): ?User
    {
        $requestedBy = $this->get('requested_by');

        return $requestedBy === null ? null : User::find((int) $requestedBy);
    }

    public function reviewer(): ?User
    {
        $reviewedBy = $this->get('reviewed_by');

        return $reviewedBy === null ? null : User::find((int) $reviewedBy);
    }

    public function event(): ?Event
    {
        $eventId = $this->get('event_id');

        return $eventId === null ? null : Event::find((int) $eventId);
    }

    public function preferredRoom(): ?Room
    {
        $roomId = $this->get('preferred_room_id');

        return $roomId === null ? null : Room::find((int) $roomId);
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

    public function isPending(): bool
    {
        return $this->get('status', 'pending') === 'pending';
    }

    public function canBeReviewed(): bool
    {
        return in_array((string) $this->get('status', 'pending'), ['pending', 'approved'], true);
    }
}
