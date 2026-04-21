<?php

declare(strict_types=1);

class ResourceRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?Resource
    {
        return Resource::find($id);
    }

    public function findBySlug(string $slug): ?Resource
    {
        $row = $this->db()->fetch(
            'SELECT * FROM resources WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        );

        return $row === null ? null : new Resource($row);
    }

    public function all(bool $onlyConsumable = false): array
    {
        $sql = 'SELECT * FROM resources';
        $params = [];

        if ($onlyConsumable) {
            $sql .= ' WHERE is_consumable = :is_consumable';
            $params['is_consumable'] = 1;
        }

        $sql .= ' ORDER BY name ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Resource => new Resource($row), $rows);
    }

    public function create(array $data): Resource
    {
        $resource = new Resource($data);
        $resource->save();

        return $resource;
    }

    public function update(int|string $id, array $data): ?Resource
    {
        $resource = $this->find($id);

        if ($resource === null) {
            return null;
        }

        $resource->fill($data);
        $resource->save();

        return $resource;
    }

    public function delete(int|string $id): bool
    {
        $resource = $this->find($id);

        return $resource?->delete() ?? false;
    }

    public function roomResources(int $resourceId): array
    {
        return $this->db()->fetchAll(
            'SELECT room_resources.*, rooms.code AS room_code, rooms.name AS room_name
             FROM room_resources
             INNER JOIN rooms ON rooms.id = room_resources.room_id
             WHERE room_resources.resource_id = :resource_id
             ORDER BY rooms.code ASC',
            ['resource_id' => $resourceId]
        );
    }

    public function saveRoomResource(int $roomId, int $resourceId, int $quantity, string $status = 'available', ?string $notes = null): void
    {
        $this->db()->insert(
            'INSERT INTO room_resources (room_id, resource_id, quantity, status, notes)
             VALUES (:room_id, :resource_id, :quantity, :status, :notes)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), status = VALUES(status), notes = VALUES(notes)',
            [
                'room_id' => $roomId,
                'resource_id' => $resourceId,
                'quantity' => $quantity,
                'status' => $status,
                'notes' => $notes,
            ]
        );
    }

    public function resourceRequests(array $filters = []): array
    {
        $sql = 'SELECT * FROM resource_requests WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['resource_id'])) {
            $sql .= ' AND resource_id = :resource_id';
            $params['resource_id'] = (int) $filters['resource_id'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= ' AND room_id = :room_id';
            $params['room_id'] = (int) $filters['room_id'];
        }

        if (!empty($filters['requested_by'])) {
            $sql .= ' AND requested_by = :requested_by';
            $params['requested_by'] = (int) $filters['requested_by'];
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        return $this->db()->fetchAll($sql, $params);
    }

    public function createResourceRequest(array $data): string
    {
        return $this->db()->insert(
            'INSERT INTO resource_requests (requested_by, event_id, room_id, resource_id, quantity, status, notes, reviewed_by, reviewed_at)
             VALUES (:requested_by, :event_id, :room_id, :resource_id, :quantity, :status, :notes, :reviewed_by, :reviewed_at)',
            [
                'requested_by' => $data['requested_by'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'resource_id' => $data['resource_id'],
                'quantity' => (int) ($data['quantity'] ?? 1),
                'status' => $data['status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
                'reviewed_by' => $data['reviewed_by'] ?? null,
                'reviewed_at' => $data['reviewed_at'] ?? null,
            ]
        );
    }

    public function reviewResourceRequest(int|string $id, string $status, ?int $reviewedBy = null): int
    {
        return $this->db()->execute(
            'UPDATE resource_requests
             SET status = :status, reviewed_by = :reviewed_by, reviewed_at = :reviewed_at
             WHERE id = :id',
            [
                'id' => $id,
                'status' => $status,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
