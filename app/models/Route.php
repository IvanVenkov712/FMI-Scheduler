<?php

declare(strict_types=1);

class Route extends Model
{
    protected string $table = 'routes';
    protected array $fillable = [
        'from_room_id',
        'to_room_id',
        'from_building_id',
        'to_building_id',
        'name',
        'description',
        'distance_meters',
        'walking_duration_minutes',
        'car_duration_minutes',
        'public_transport_duration_minutes',
        'path_data',
        'is_accessible',
        'is_bidirectional',
        'status',
    ];

    public function fromRoom(): ?Room
    {
        $roomId = $this->get('from_room_id');

        return $roomId === null ? null : (new RoomRepository())->find((int) $roomId);
    }

    public function toRoom(): ?Room
    {
        $roomId = $this->get('to_room_id');

        return $roomId === null ? null : (new RoomRepository())->find((int) $roomId);
    }

    public function fromBuilding(): ?Building
    {
        $buildingId = $this->get('from_building_id');

        return $buildingId === null ? null : (new BuildingRepository())->find((int) $buildingId);
    }

    public function toBuilding(): ?Building
    {
        $buildingId = $this->get('to_building_id');

        return $buildingId === null ? null : (new BuildingRepository())->find((int) $buildingId);
    }

    public function steps(): array
    {
        return $this->exists() ? (new RouteRepository())->steps((int) $this->primaryValue()) : [];
    }

    public function path(): array
    {
        $value = $this->get('path_data');

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function isAccessible(): bool
    {
        return (int) $this->get('is_accessible', 1) === 1;
    }

    public function isBidirectional(): bool
    {
        return (int) $this->get('is_bidirectional', 1) === 1;
    }

    public function isActive(): bool
    {
        return $this->get('status', 'active') === 'active';
    }

    public function isInterBuilding(): bool
    {
        return $this->get('from_building_id') !== null && $this->get('to_building_id') !== null;
    }

    public function durationFor(string $movementType = ''): ?int
    {
        $durations = $this->durations();

        if ($movementType !== '' && isset($durations[$movementType])) {
            return $durations[$movementType];
        }

        $available = array_filter($durations, static fn (?int $duration): bool => $duration !== null);

        return $available === [] ? null : min($available);
    }

    public function preferredMovementType(): ?string
    {
        $durations = array_filter(
            $this->durations(),
            static fn (?int $duration): bool => $duration !== null
        );

        if ($durations === []) {
            return null;
        }

        asort($durations, SORT_NUMERIC);

        return array_key_first($durations);
    }

    private function durations(): array
    {
        return [
            'walking' => $this->durationValue('walking_duration_minutes'),
            'car' => $this->durationValue('car_duration_minutes'),
            'public_transport' => $this->durationValue('public_transport_duration_minutes'),
        ];
    }

    private function durationValue(string $field): ?int
    {
        $value = $this->get($field);

        return $value === null || $value === '' ? null : (int) $value;
    }
}
