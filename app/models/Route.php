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
        'movement_type',
        'distance_meters',
        'duration_minutes',
        'path_data',
        'is_accessible',
        'status',
    ];

    public function fromRoom(): ?Room
    {
        $roomId = $this->get('from_room_id');

        return $roomId === null ? null : Room::find((int) $roomId);
    }

    public function toRoom(): ?Room
    {
        $roomId = $this->get('to_room_id');

        return $roomId === null ? null : Room::find((int) $roomId);
    }

    public function fromBuilding(): ?Building
    {
        $buildingId = $this->get('from_building_id');

        return $buildingId === null ? null : Building::find((int) $buildingId);
    }

    public function toBuilding(): ?Building
    {
        $buildingId = $this->get('to_building_id');

        return $buildingId === null ? null : Building::find((int) $buildingId);
    }

    public function steps(): array
    {
        if (!$this->exists()) {
            return [];
        }

        return $this->db()->fetchAll(
            'SELECT * FROM route_steps WHERE route_id = :route_id ORDER BY step_order ASC',
            ['route_id' => $this->primaryValue()]
        );
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

    public function isActive(): bool
    {
        return $this->get('status', 'active') === 'active';
    }

    public function isInterBuilding(): bool
    {
        return $this->get('from_building_id') !== null && $this->get('to_building_id') !== null;
    }
}
