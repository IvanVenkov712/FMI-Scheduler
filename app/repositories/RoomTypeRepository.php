<?php

declare(strict_types=1);

class RoomTypeRepository extends GenericModelRepository
{

    public function all(): array
    {
        return $this->db()->fetchAll('SELECT * FROM room_types ORDER BY name ASC');
    }

    public function find(int|string $id): ?RoomType
    {
        return $this->findModel(RoomType::class, $id);
    }

    public function rooms(int $roomTypeId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM rooms WHERE room_type_id = :room_type_id ORDER BY code ASC',
            ['room_type_id' => $roomTypeId]
        );

        return array_map(static fn (array $row): Room => new Room($row), $rows);
    }
}


