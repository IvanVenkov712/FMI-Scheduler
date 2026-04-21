<?php

declare(strict_types=1);

class Floor extends Model
{
    protected string $table = 'floors';
    protected array $fillable = [
        'building_id',
        'number',
        'name',
        'description',
        'plan_data',
        'ascii_plan',
        'capacity',
        'width_units',
        'height_units',
        'status',
    ];

    public function building(): ?Building
    {
        $buildingId = $this->get('building_id');

        return $buildingId === null ? null : Building::find((int) $buildingId);
    }

    public function rooms(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT * FROM rooms WHERE floor_id = :floor_id ORDER BY code ASC',
            ['floor_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): Room => new Room($row), $rows);
    }

    public function points(): array
    {
        if (!$this->exists()) {
            return [];
        }

        return $this->db()->fetchAll(
            'SELECT * FROM floor_points WHERE floor_id = :floor_id ORDER BY type ASC, code ASC',
            ['floor_id' => $this->primaryValue()]
        );
    }

    public function activeRooms(): array
    {
        return array_values(array_filter(
            $this->rooms(),
            static fn (Room $room): bool => $room->isAvailable()
        ));
    }

    public function totalRoomCapacity(): int
    {
        return array_reduce(
            $this->rooms(),
            static fn (int $sum, Room $room): int => $sum + (int) $room->get('capacity', 0),
            0
        );
    }

    public function isActive(): bool
    {
        return $this->get('status', 'active') === 'active';
    }
}
