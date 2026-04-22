<?php

declare(strict_types=1);

class RoomResource extends Model
{
    protected string $table = 'room_resources';
    protected array $fillable = [
        'room_id',
        'resource_id',
        'quantity',
        'status',
        'notes',
    ];

    public function room(): ?Room
    {
        $roomId = $this->get('room_id');

        return $roomId === null ? null : (new RoomRepository())->find((int) $roomId);
    }

    public function resource(): ?Resource
    {
        $resourceId = $this->get('resource_id');

        return $resourceId === null ? null : (new ResourceRepository())->find((int) $resourceId);
    }

    public function isAvailable(): bool
    {
        return $this->get('status', 'available') === 'available';
    }

    public function quantityValue(): int
    {
        return (int) $this->get('quantity', 0);
    }
}
