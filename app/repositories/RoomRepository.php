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
        return Room::find($id);
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

    public function create(array $data): Room
    {
        $room = new Room($data);
        $room->save();

        return $room;
    }

    public function update(int|string $id, array $data): ?Room
    {
        $room = $this->find($id);

        if ($room === null) {
            return null;
        }

        $room->fill($data);
        $room->save();

        return $room;
    }

    public function delete(int|string $id): bool
    {
        $room = $this->find($id);

        return $room?->delete() ?? false;
    }

    public function availableBetween(
        int $capacity,
        string $startsAt,
        string $endsAt,
        string $deliveryMode = 'physical',
        array $requiredResourceIds = [],
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
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
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
                  AND events.starts_at < :ends_at
                  AND events.ends_at > :starts_at
                  AND events.status <> "cancelled"
            )
            AND NOT EXISTS (
                SELECT 1 FROM room_unavailability
                WHERE room_unavailability.room_id = rooms.id
                  AND room_unavailability.starts_at < :ends_at
                  AND room_unavailability.ends_at > :starts_at
            )';

        foreach (array_values($requiredResourceIds) as $index => $resourceId) {
            $resourceKey = 'resource_' . $index;
            $sql .= '
                AND EXISTS (
                    SELECT 1 FROM room_resources
                    WHERE room_resources.room_id = rooms.id
                      AND room_resources.resource_id = :' . $resourceKey . '
                      AND room_resources.status = "available"
                )';
            $params[$resourceKey] = (int) $resourceId;
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
