<?php

declare(strict_types=1);

class Room extends Model
{
    protected string $table = 'rooms';
    protected array $fillable = [
        'floor_id',
        'room_type_id',
        'code',
        'name',
        'description',
        'capacity',
        'covid_capacity',
        'x',
        'y',
        'width',
        'height',
        'online_url',
        'access_key',
        'status',
    ];

    public function floor(): ?Floor
    {
        $floorId = $this->get('floor_id');

        return $floorId === null ? null : (new FloorRepository())->find((int) $floorId);
    }

    public function roomType(): ?RoomType
    {
        $typeId = $this->get('room_type_id');

        return $typeId === null ? null : (new RoomTypeRepository())->find((int) $typeId);
    }

    public function resources(): array
    {
        return $this->exists() ? (new RoomRepository())->resourcesForRoomModels((int) $this->primaryValue()) : [];
    }

    public function events(string $from = '', string $to = ''): array
    {
        return $this->exists() ? (new RoomRepository())->eventsForRoom((int) $this->primaryValue(), $from, $to) : [];
    }

    public function unavailability(string $from = '', string $to = ''): array
    {
        return $this->exists() ? (new RoomRepository())->unavailability((int) $this->primaryValue(), $from, $to) : [];
    }

    public function isAvailableBetween(string $startsAt, string $endsAt): bool
    {
        if (!$this->exists() || !$this->isAvailable()) {
            return false;
        }

        $repository = new RoomRepository();
        if ($repository->hasEventConflict((int) $this->primaryValue(), $startsAt, $endsAt)) {
            return false;
        }

        return !$repository->hasUnavailabilityConflict((int) $this->primaryValue(), $startsAt, $endsAt);
    }

    public function effectiveCapacity(bool $preferCovid = true): int
    {
        if ($preferCovid && $this->get('covid_capacity') !== null) {
            return (int) $this->get('covid_capacity');
        }

        return (int) $this->get('capacity', 0);
    }

    public function isAvailable(): bool
    {
        return $this->get('status', 'available') === 'available';
    }

    public function supportsOnline(): bool
    {
        $roomType = $this->roomType();

        if ($roomType !== null) {
            return $roomType->supportsOnline();
        }

        return (string) $this->get('online_url', '') !== '';
    }
}
