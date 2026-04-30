<?php

declare(strict_types=1);

class RoomRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?Room
    {
        return (new GenericModelRepository($this->database))->find(Room::class, $id);
    }

    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        return array_map(
            static fn (object $room): Room => $room,
            (new GenericModelRepository($this->database))->all(Room::class, $orderBy, $direction)
        );
    }

    public function findByCode(string $code): ?Room
    {
        $row = $this->db()->fetch(
            'SELECT * FROM rooms WHERE code = :code LIMIT 1',
            ['code' => $code]
        );

        return $row === null ? null : new Room($row);
    }

    public function byFloor(int $floorId, bool $onlyAvailable = false): array
    {
        $sql = 'SELECT * FROM rooms WHERE floor_id = :floor_id';
        $params = ['floor_id' => $floorId];

        if ($onlyAvailable) {
            $sql .= ' AND status = :status';
            $params['status'] = 'available';
        }

        $sql .= ' ORDER BY code ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Room => new Room($row), $rows);
    }

    public function listing(array $filters = []): array
    {
        $sql = 'SELECT * FROM rooms WHERE 1=1';
        $params = [];

        if (!empty($filters['floor_id'])) {
            $sql .= ' AND floor_id = :floor_id';
            $params['floor_id'] = (int) $filters['floor_id'];
        }

        if (!empty($filters['room_type_id'])) {
            $sql .= ' AND room_type_id = :room_type_id';
            $params['room_type_id'] = (int) $filters['room_type_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        $sql .= ' ORDER BY code ASC, id ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Room => new Room($row), $rows);
    }

    public function roomTypes(): array
    {
        return $this->db()->fetchAll('SELECT * FROM room_types ORDER BY name ASC');
    }

    public function resourcesForRoomModels(int $roomId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT room_resources.*, resources.name, resources.slug, resources.unit, resources.is_consumable
             FROM room_resources
             INNER JOIN resources ON resources.id = room_resources.resource_id
             WHERE room_resources.room_id = :room_id
             ORDER BY resources.name ASC',
            ['room_id' => $roomId]
        );

        return array_map(static fn (array $row): RoomResource => new RoomResource($row), $rows);
    }

    public function create(array $data): Room
    {
        $room = new Room($data);
        (new GenericModelRepository($this->database))->save($room);

        return $room;
    }

    public function update(int|string $id, array $data): ?Room
    {
        $room = $this->find($id);

        if ($room === null) {
            return null;
        }

        $room->fill($data);
        (new GenericModelRepository($this->database))->save($room);

        return $room;
    }

    public function delete(int|string $id): bool
    {
        $room = $this->find($id);

        return $room !== null
            ? (new GenericModelRepository($this->database))->delete($room)
            : false;
    }

    public function availableBetween(
        int $capacity,
        string $startsAt,
        string $endsAt,
        string $deliveryMode = 'physical',
        array $requiredResources = [],
        bool $preferCovidCapacity = true
    ): array {
        $capacityColumn = $preferCovidCapacity
            ? 'COALESCE(rooms.covid_capacity, rooms.capacity)'
            : 'rooms.capacity';

        $sql = 'SELECT rooms.*, room_types.slug AS room_type
                FROM rooms
                INNER JOIN room_types ON room_types.id = rooms.room_type_id
                WHERE rooms.status = "available"
                  AND ' . $capacityColumn . ' >= :capacity';
        $params = [
            'capacity' => $capacity,
            'event_window_starts_at' => $startsAt,
            'event_window_ends_at' => $endsAt,
            'unavailability_window_starts_at' => $startsAt,
            'unavailability_window_ends_at' => $endsAt,
        ];

        if ($deliveryMode === 'online') {
            $sql .= ' AND room_types.supports_online = 1';
        } elseif ($deliveryMode === 'hybrid') {
            $sql .= ' AND room_types.supports_online = 1 AND room_types.is_physical = 1';
        } else {
            $sql .= ' AND room_types.is_physical = 1';
        }

        $sql .= '
            AND NOT EXISTS (
                SELECT 1 FROM events
                WHERE events.room_id = rooms.id
                  AND events.starts_at < :event_window_ends_at
                  AND events.ends_at > :event_window_starts_at
                  AND events.status <> "cancelled"
            )
            AND NOT EXISTS (
                SELECT 1 FROM room_unavailability
                WHERE room_unavailability.room_id = rooms.id
                  AND room_unavailability.starts_at < :unavailability_window_ends_at
                  AND room_unavailability.ends_at > :unavailability_window_starts_at
            )';

        foreach (array_values(ResourceRequirementHelper::normalize($requiredResources)) as $index => $requirement) {
            $resourceKey = 'resource_' . $index;
            $quantityKey = 'resource_quantity_' . $index;
            $sql .= '
                AND EXISTS (
                    SELECT 1 FROM room_resources
                    WHERE room_resources.room_id = rooms.id
                      AND room_resources.resource_id = :' . $resourceKey . '
                      AND room_resources.quantity >= :' . $quantityKey . '
                      AND room_resources.status = "available"
                )';
            $params[$resourceKey] = (int) ($requirement['resource_id'] ?? 0);
            $params[$quantityKey] = (int) ($requirement['quantity'] ?? 1);
        }

        $sql .= ' ORDER BY ' . $capacityColumn . ' ASC, rooms.code ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Room => new Room($row), $rows);
    }

    public function saveResources(int $roomId, array $resources): void
    {
        $this->db()->transaction(function (Database $db) use ($roomId, $resources): void {
            $db->execute('DELETE FROM room_resources WHERE room_id = :room_id', ['room_id' => $roomId]);

            foreach ($resources as $resource) {
                if (!is_array($resource) || empty($resource['resource_id'])) {
                    continue;
                }

                $db->insert(
                    'INSERT INTO room_resources (room_id, resource_id, quantity, status, notes)
                     VALUES (:room_id, :resource_id, :quantity, :status, :notes)',
                    [
                        'room_id' => $roomId,
                        'resource_id' => (int) $resource['resource_id'],
                        'quantity' => (int) ($resource['quantity'] ?? 1),
                        'status' => (string) ($resource['status'] ?? 'available'),
                        'notes' => $resource['notes'] ?? null,
                    ]
                );
            }
        });
    }

    public function unavailability(int $roomId, string $from = '', string $to = ''): array
    {
        $sql = 'SELECT * FROM room_unavailability WHERE room_id = :room_id';
        $params = ['room_id' => $roomId];

        if ($from !== '' && $to !== '') {
            $sql .= ' AND starts_at < :to_date AND ends_at > :from_date';
            $params['from_date'] = $from;
            $params['to_date'] = $to;
        }

        $sql .= ' ORDER BY starts_at ASC';

        return $this->db()->fetchAll($sql, $params);
    }

    public function eventsForRoom(int $roomId, string $from = '', string $to = ''): array
    {
        $sql = 'SELECT * FROM events WHERE room_id = :room_id';
        $params = ['room_id' => $roomId];

        if ($from !== '' && $to !== '') {
            $sql .= ' AND starts_at < :to_date AND ends_at > :from_date';
            $params['from_date'] = $from;
            $params['to_date'] = $to;
        }

        $sql .= ' ORDER BY starts_at ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function hasEventConflict(int $roomId, string $startsAt, string $endsAt): bool
    {
        return $this->db()->fetch(
            'SELECT id
             FROM events
             WHERE room_id = :room_id
               AND starts_at < :ends_at
               AND ends_at > :starts_at
               AND status <> "cancelled"
             LIMIT 1',
            [
                'room_id' => $roomId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        ) !== null;
    }

    public function hasUnavailabilityConflict(int $roomId, string $startsAt, string $endsAt): bool
    {
        return $this->db()->fetch(
            'SELECT id
             FROM room_unavailability
             WHERE room_id = :room_id
               AND starts_at < :ends_at
               AND ends_at > :starts_at
             LIMIT 1',
            [
                'room_id' => $roomId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        ) !== null;
    }

    public function createUnavailability(array $data): string
    {
        return $this->db()->insert(
            'INSERT INTO room_unavailability (room_id, reason, starts_at, ends_at, created_by)
             VALUES (:room_id, :reason, :starts_at, :ends_at, :created_by)',
            [
                'room_id' => $data['room_id'],
                'reason' => $data['reason'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'created_by' => $data['created_by'] ?? null,
            ]
        );
    }

    public function connections(int $roomId): array
    {
        return $this->db()->fetchAll(
            'SELECT *
             FROM room_connections
             WHERE from_room_id = :room_id OR to_room_id = :room_id
             ORDER BY walking_minutes ASC, id ASC',
            ['room_id' => $roomId]
        );
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
