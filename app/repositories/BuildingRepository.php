<?php

declare(strict_types=1);

class BuildingRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?Building
    {
        return (new GenericModelRepository($this->database))->find(Building::class, $id);
    }

    public function findByCode(string $code): ?Building
    {
        $row = $this->db()->fetch(
            'SELECT * FROM buildings WHERE code = :code LIMIT 1',
            ['code' => $code]
        );

        return $row === null ? null : new Building($row);
    }

    public function all(bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM buildings';

        if ($onlyActive) {
            $sql .= ' WHERE status = "active"';
        }

        $sql .= ' ORDER BY name ASC';

        $rows = $this->db()->fetchAll($sql);

        return array_map(static fn (array $row): Building => new Building($row), $rows);
    }

    public function listing(array $filters = []): array
    {
        $sql = 'SELECT * FROM buildings WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['orientation'])) {
            $sql .= ' AND orientation = :orientation';
            $params['orientation'] = $filters['orientation'];
        }

        $sql .= ' ORDER BY name ASC, id ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Building => new Building($row), $rows);
    }

    public function create(array $data): Building
    {
        $building = new Building($data);
        (new GenericModelRepository($this->database))->save($building);

        return $building;
    }

    public function update(int|string $id, array $data): ?Building
    {
        $building = $this->find($id);

        if ($building === null) {
            return null;
        }

        $building->fill($data);
        (new GenericModelRepository($this->database))->save($building);

        return $building;
    }

    public function delete(int|string $id): bool
    {
        $building = $this->find($id);

        return $building !== null
            ? (new GenericModelRepository($this->database))->delete($building)
            : false;
    }

    public function withStatistics(): array
    {
        return $this->db()->fetchAll(
            'SELECT buildings.*,
                    COUNT(DISTINCT floors.id) AS floors_total,
                    COUNT(DISTINCT rooms.id) AS rooms_total,
                    COALESCE(SUM(rooms.capacity), 0) AS total_capacity
             FROM buildings
             LEFT JOIN floors ON floors.building_id = buildings.id
             LEFT JOIN rooms ON rooms.floor_id = floors.id
             GROUP BY buildings.id
             ORDER BY buildings.name ASC'
        );
    }

    public function floors(int $buildingId, bool $onlyActive = false): array
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

    public function routesForBuilding(int $buildingId, string $column): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT * FROM routes
             WHERE {$column} = :building_id
             ORDER BY LEAST(
                IFNULL(walking_duration_minutes, 65535),
                IFNULL(car_duration_minutes, 65535),
                IFNULL(public_transport_duration_minutes, 65535)
             ) ASC, name ASC",
            ['building_id' => $buildingId]
        );

        return array_map(static fn (array $row): Route => new Route($row), $rows);
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
