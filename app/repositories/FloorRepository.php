<?php

declare(strict_types=1);

class FloorRepository extends GenericModelRepository
{

    public function find(int|string $id): ?Floor
    {
        return $this->findModel(Floor::class, $id);
    }

    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        return array_map(
            static fn (object $floor): Floor => $floor,
            $this->allModels(Floor::class, $orderBy, $direction)
        );
    }

    public function byBuilding(int $buildingId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM floors WHERE building_id = :building_id';
        $params = ['building_id' => $buildingId];

        if ($onlyActive) {
            $sql .= ' AND status = :status';
            $params['status'] = 'active';
        }

        $sql .= ' ORDER BY number ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Floor => new Floor($row), $rows);
    }

    public function listing(array $filters = []): array
    {
        $sql = 'SELECT * FROM floors WHERE 1=1';
        $params = [];

        if (!empty($filters['building_id'])) {
            $sql .= ' AND building_id = :building_id';
            $params['building_id'] = (int) $filters['building_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        $sql .= ' ORDER BY building_id ASC, number ASC, id ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Floor => new Floor($row), $rows);
    }

    public function create(array $data): Floor
    {
        $floor = new Floor($data);
        $this->saveModel($floor);

        return $floor;
    }

    public function update(int|string $id, array $data): ?Floor
    {
        $floor = $this->find($id);

        if ($floor === null) {
            return null;
        }

        $floor->fill($data);
        $this->saveModel($floor);

        return $floor;
    }

    public function delete(int|string $id): bool
    {
        $floor = $this->find($id);

        return $floor !== null
            ? $this->deleteModel($floor)
            : false;
    }

    public function points(int $floorId): array
    {
        return $this->db()->fetchAll(
            'SELECT * FROM floor_points WHERE floor_id = :floor_id ORDER BY type ASC, code ASC',
            ['floor_id' => $floorId]
        );
    }

    public function savePoints(int $floorId, array $points): void
    {
        $this->db()->transaction(function (Database $db) use ($floorId, $points): void {
            $db->execute('DELETE FROM floor_points WHERE floor_id = :floor_id', ['floor_id' => $floorId]);

            foreach ($points as $point) {
                if (!is_array($point) || empty($point['code']) || empty($point['name']) || empty($point['type'])) {
                    continue;
                }

                $db->insert(
                    'INSERT INTO floor_points (floor_id, code, name, type, x, y, is_accessible)
                     VALUES (:floor_id, :code, :name, :type, :x, :y, :is_accessible)',
                    [
                        'floor_id' => $floorId,
                        'code' => (string) $point['code'],
                        'name' => (string) $point['name'],
                        'type' => (string) $point['type'],
                        'x' => (int) ($point['x'] ?? 0),
                        'y' => (int) ($point['y'] ?? 0),
                        'is_accessible' => !empty($point['is_accessible']) ? 1 : 0,
                    ]
                );
            }
        });
    }

    public function updateAsciiPlan(int $floorId, string $asciiPlan, ?string $planData = null): ?Floor
    {
        $data = ['ascii_plan' => $asciiPlan];

        if ($planData !== null) {
            $data['plan_data'] = $planData;
        }

        return $this->update($floorId, $data);
    }

    public function rooms(int $floorId, bool $onlyAvailable = false): array
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
}


