<?php

declare(strict_types=1);

class Resource extends Model
{
    protected string $table = 'resources';
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'unit',
        'is_consumable',
    ];

    public function roomResources(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT * FROM room_resources WHERE resource_id = :resource_id ORDER BY room_id ASC',
            ['resource_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): RoomResource => new RoomResource($row), $rows);
    }

    public function rooms(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT rooms.*
             FROM room_resources
             INNER JOIN rooms ON rooms.id = room_resources.room_id
             WHERE room_resources.resource_id = :resource_id
             ORDER BY rooms.code ASC',
            ['resource_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): Room => new Room($row), $rows);
    }

    public function requests(): array
    {
        if (!$this->exists()) {
            return [];
        }

        return $this->db()->fetchAll(
            'SELECT * FROM resource_requests WHERE resource_id = :resource_id ORDER BY created_at DESC',
            ['resource_id' => $this->primaryValue()]
        );
    }

    public function isConsumable(): bool
    {
        return (int) $this->get('is_consumable', 0) === 1;
    }
}
