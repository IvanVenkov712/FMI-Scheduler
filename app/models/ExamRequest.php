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
        'online_platform',
        'online_url',
        'online_access_key',
        'online_access_password',
        'online_host_user_id',
        'online_moderator_name',
        'online_access_instructions',
        'required_resources',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    public function requester(): ?User
    {
        $requestedBy = $this->get('requested_by');

        return $requestedBy === null ? null : (new UserRepository())->find((int) $requestedBy);
    }

    public function reviewer(): ?User
    {
        $reviewedBy = $this->get('reviewed_by');

        return $reviewedBy === null ? null : (new UserRepository())->find((int) $reviewedBy);
    }

    public function event(): ?Event
    {
        $eventId = $this->get('event_id');

        return $eventId === null ? null : (new EventRepository())->find((int) $eventId);
    }

    public function preferredRoom(): ?Room
    {
        $roomId = $this->get('preferred_room_id');

        return $roomId === null ? null : (new RoomRepository())->find((int) $roomId);
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

    public function onlineSessionData(): array
    {
        return [
            'platform' => $this->get('online_platform'),
            'url' => $this->get('online_url'),
            'access_key' => $this->get('online_access_key'),
            'access_password' => $this->get('online_access_password'),
            'host_user_id' => $this->get('online_host_user_id'),
            'moderator_name' => $this->get('online_moderator_name'),
            'access_instructions' => $this->get('online_access_instructions'),
            'starts_at' => $this->get('preferred_starts_at'),
            'ends_at' => $this->get('preferred_ends_at'),
        ];
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
