<?php

declare(strict_types=1);

class RouteRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?Route
    {
        return Route::find($id);
    }

    public function all(bool $onlyActive = false, string $movementType = ''): array
    {
        $sql = 'SELECT * FROM routes WHERE 1=1';
        $params = [];

        if ($onlyActive) {
            $sql .= ' AND status = :status';
            $params['status'] = 'active';
        }

        $durationColumn = $this->durationColumn($movementType);

        if ($durationColumn !== null) {
            $sql .= sprintf(' AND %s IS NOT NULL', $durationColumn);
        }

        $sql .= ' ORDER BY ' . $this->orderByDuration($durationColumn) . ', name ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Route => new Route($row), $rows);
    }

    public function create(array $data): Route
    {
        if (isset($data['path_data']) && is_array($data['path_data'])) {
            $data['path_data'] = json_encode($data['path_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $route = new Route($data);
        $route->save();

        return $route;
    }

    public function update(int|string $id, array $data): ?Route
    {
        $route = $this->find($id);

        if ($route === null) {
            return null;
        }

        if (isset($data['path_data']) && is_array($data['path_data'])) {
            $data['path_data'] = json_encode($data['path_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $route->fill($data);
        $route->save();

        return $route;
    }

    public function delete(int|string $id): bool
    {
        $route = $this->find($id);

        return $route?->delete() ?? false;
    }

    public function betweenRooms(int $fromRoomId, int $toRoomId, bool $accessibleOnly = false, string $movementType = ''): array
    {
        $sql = 'SELECT * FROM routes
                WHERE from_room_id = :from_room_id
                  AND to_room_id = :to_room_id';
        $params = [
            'from_room_id' => $fromRoomId,
            'to_room_id' => $toRoomId,
        ];

        if ($accessibleOnly) {
            $sql .= ' AND is_accessible = :is_accessible';
            $params['is_accessible'] = 1;
        }

        $durationColumn = $this->durationColumn($movementType);

        if ($durationColumn !== null) {
            $sql .= sprintf(' AND %s IS NOT NULL', $durationColumn);
        }

        $sql .= ' AND status = "active" ORDER BY ' . $this->orderByDuration($durationColumn) . ', id ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Route => new Route($row), $rows);
    }

    public function betweenBuildings(int $fromBuildingId, int $toBuildingId, string $movementType = ''): array
    {
        $sql = 'SELECT * FROM routes
                WHERE from_building_id = :from_building_id
                  AND to_building_id = :to_building_id
                  AND status = "active"';
        $params = [
            'from_building_id' => $fromBuildingId,
            'to_building_id' => $toBuildingId,
        ];

        $durationColumn = $this->durationColumn($movementType);

        if ($durationColumn !== null) {
            $sql .= sprintf(' AND %s IS NOT NULL', $durationColumn);
        }

        $sql .= ' ORDER BY ' . $this->orderByDuration($durationColumn) . ', id ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Route => new Route($row), $rows);
    }

    public function steps(int $routeId): array
    {
        return $this->db()->fetchAll(
            'SELECT * FROM route_steps WHERE route_id = :route_id ORDER BY step_order ASC',
            ['route_id' => $routeId]
        );
    }

    public function saveSteps(int $routeId, array $steps): void
    {
        $this->db()->transaction(function (Database $db) use ($routeId, $steps): void {
            $db->execute('DELETE FROM route_steps WHERE route_id = :route_id', ['route_id' => $routeId]);

            foreach ($steps as $index => $step) {
                if (!is_array($step) || empty($step['instruction'])) {
                    continue;
                }

                $db->insert(
                    'INSERT INTO route_steps (route_id, step_order, instruction, point_type, room_id, floor_point_id, building_id, duration_minutes)
                     VALUES (:route_id, :step_order, :instruction, :point_type, :room_id, :floor_point_id, :building_id, :duration_minutes)',
                    [
                        'route_id' => $routeId,
                        'step_order' => (int) ($step['step_order'] ?? ($index + 1)),
                        'instruction' => (string) $step['instruction'],
                        'point_type' => (string) ($step['point_type'] ?? 'text'),
                        'room_id' => $step['room_id'] ?? null,
                        'floor_point_id' => $step['floor_point_id'] ?? null,
                        'building_id' => $step['building_id'] ?? null,
                        'duration_minutes' => (int) ($step['duration_minutes'] ?? 0),
                    ]
                );
            }
        });
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }

    private function durationColumn(string $movementType): ?string
    {
        return match ($movementType) {
            'walking' => 'walking_duration_minutes',
            'car' => 'car_duration_minutes',
            'public_transport' => 'public_transport_duration_minutes',
            default => null,
        };
    }

    private function orderByDuration(?string $durationColumn): string
    {
        if ($durationColumn !== null) {
            return sprintf('%s IS NULL ASC, %s ASC, distance_meters ASC', $durationColumn, $durationColumn);
        }

        return 'LEAST(
            IFNULL(walking_duration_minutes, 65535),
            IFNULL(car_duration_minutes, 65535),
            IFNULL(public_transport_duration_minutes, 65535)
        ) ASC, distance_meters ASC';
    }
}
