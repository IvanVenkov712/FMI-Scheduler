<?php

declare(strict_types=1);

class OnlineSession extends Model
{
    protected string $table = 'online_sessions';
    protected array $fillable = [
        'event_id',
        'platform',
        'url',
        'access_key',
        'access_password',
        'host_user_id',
        'moderator_name',
        'access_instructions',
        'starts_at',
        'ends_at',
    ];

    public function event(): ?Event
    {
        $eventId = $this->get('event_id');

        return $eventId === null ? null : (new EventRepository())->find((int) $eventId);
    }

    public function host(): ?User
    {
        $hostId = $this->get('host_user_id');

        return $hostId === null ? null : (new UserRepository())->find((int) $hostId);
    }

    public function isLive(?DateTimeInterface $at = null): bool
    {
        $at ??= new DateTimeImmutable();
        $startsAt = $this->get('starts_at');
        $endsAt = $this->get('ends_at');

        if (!is_string($startsAt) || !is_string($endsAt) || $startsAt === '' || $endsAt === '') {
            return false;
        }

        $start = new DateTimeImmutable($startsAt);
        $end = new DateTimeImmutable($endsAt);

        return $at >= $start && $at <= $end;
    }
}
