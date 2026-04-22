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
        if ($fromRoomId <= 0 || $toRoomId <= 0) {
            return [];
        }

        $candidates = [];
        $bestPath = $this->shortestRoomPath($fromRoomId, $toRoomId, $accessibleOnly, $movementType);

        if ($bestPath !== null) {
            $candidates[$this->candidateKey($bestPath)] = $bestPath;
        }

        foreach ($this->directRoomRoutes($fromRoomId, $toRoomId, $accessibleOnly, $movementType) as $route) {
            $key = $this->candidateKey($route);
            if (!isset($candidates[$key])) {
                $candidates[$key] = $route;
            }
        }

        $result = array_values($candidates);
        usort(
            $result,
            fn (Route $left, Route $right): int => $this->compareRoutes($left, $right, $movementType)
        );

        return $result;
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

    public function directRoomRoutes(int $fromRoomId, int $toRoomId, bool $accessibleOnly = false, string $movementType = ''): array
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

    private function shortestRoomPath(int $fromRoomId, int $toRoomId, bool $accessibleOnly, string $movementType): ?Route
    {
        if ($fromRoomId === $toRoomId) {
            return $this->identityRoute($fromRoomId);
        }

        $graph = $this->roomGraph($accessibleOnly, $movementType);
        if (!isset($graph[$fromRoomId])) {
            return null;
        }

        $distances = [$fromRoomId => 0];
        $previous = [];
        $visited = [];
        $queue = [$fromRoomId => 0];

        while ($queue !== []) {
            asort($queue, SORT_NUMERIC);
            $currentRoomId = (int) array_key_first($queue);
            unset($queue[$currentRoomId]);

            if (isset($visited[$currentRoomId])) {
                continue;
            }

            $visited[$currentRoomId] = true;

            if ($currentRoomId === $toRoomId) {
                break;
            }

            foreach ($graph[$currentRoomId] ?? [] as $edge) {
                $nextRoomId = (int) ($edge['to_room_id'] ?? 0);
                $cost = (int) ($edge['duration_minutes'] ?? PHP_INT_MAX);

                if ($nextRoomId <= 0 || $cost < 0) {
                    continue;
                }

                $newDistance = $distances[$currentRoomId] + $cost;
                if (!isset($distances[$nextRoomId]) || $newDistance < $distances[$nextRoomId]) {
                    $distances[$nextRoomId] = $newDistance;
                    $previous[$nextRoomId] = [
                        'from_room_id' => $currentRoomId,
                        'edge' => $edge,
                    ];
                    $queue[$nextRoomId] = $newDistance;
                }
            }
        }

        if (!isset($previous[$toRoomId])) {
            return null;
        }

        $segments = [];
        $cursor = $toRoomId;

        while (isset($previous[$cursor])) {
            $segments[] = $previous[$cursor]['edge'];
            $cursor = (int) $previous[$cursor]['from_room_id'];
        }

        $segments = array_reverse($segments);

        return $this->resolvedRouteFromSegments($fromRoomId, $toRoomId, $segments, $movementType);
    }

    private function roomGraph(bool $accessibleOnly, string $movementType): array
    {
        $graph = [];

        foreach ($this->routeEdges($accessibleOnly, $movementType) as $edge) {
            $graph[(int) $edge['from_room_id']][] = $edge;
        }

        foreach ($this->connectionEdges($accessibleOnly, $movementType) as $edge) {
            $graph[(int) $edge['from_room_id']][] = $edge;
        }

        return $graph;
    }

    private function routeEdges(bool $accessibleOnly, string $movementType): array
    {
        $sql = 'SELECT *
                FROM routes
                WHERE status = "active"
                  AND from_room_id IS NOT NULL
                  AND to_room_id IS NOT NULL';
        $params = [];

        if ($accessibleOnly) {
            $sql .= ' AND is_accessible = :is_accessible';
            $params['is_accessible'] = 1;
        }

        $durationColumn = $this->durationColumn($movementType);
        if ($durationColumn !== null) {
            $sql .= sprintf(' AND %s IS NOT NULL', $durationColumn);
        }

        $sql .= ' ORDER BY id ASC';

        $rows = $this->db()->fetchAll($sql, $params);
        $edges = [];

        foreach ($rows as $row) {
            $route = new Route($row);
            $duration = $route->durationFor($movementType);

            if ($duration === null) {
                continue;
            }

            $forwardSteps = $this->steps((int) ($row['id'] ?? 0));
            $edges[] = [
                'source' => 'route',
                'route_id' => (int) ($row['id'] ?? 0),
                'from_room_id' => (int) ($row['from_room_id'] ?? 0),
                'to_room_id' => (int) ($row['to_room_id'] ?? 0),
                'duration_minutes' => $duration,
                'distance_meters' => (int) ($row['distance_meters'] ?? 0),
                'is_accessible' => (int) ($row['is_accessible'] ?? 1),
                'movement_type' => $movementType !== '' ? $movementType : ($route->preferredMovementType() ?? 'walking'),
                'name' => (string) ($row['name'] ?? ''),
                'route_attributes' => $row,
                'steps' => $forwardSteps,
            ];

            $reverse = $row;
            $reverse['from_room_id'] = $row['to_room_id'];
            $reverse['to_room_id'] = $row['from_room_id'];
            $reverse['name'] = sprintf('%s (reverse)', (string) ($row['name'] ?? 'Route'));

            $edges[] = [
                'source' => 'route',
                'route_id' => (int) ($row['id'] ?? 0),
                'from_room_id' => (int) ($row['to_room_id'] ?? 0),
                'to_room_id' => (int) ($row['from_room_id'] ?? 0),
                'duration_minutes' => $duration,
                'distance_meters' => (int) ($row['distance_meters'] ?? 0),
                'is_accessible' => (int) ($row['is_accessible'] ?? 1),
                'movement_type' => $movementType !== '' ? $movementType : ($route->preferredMovementType() ?? 'walking'),
                'name' => (string) $reverse['name'],
                'route_attributes' => $reverse,
                'steps' => $this->reverseSteps($forwardSteps),
            ];
        }

        return $edges;
    }

    private function connectionEdges(bool $accessibleOnly, string $movementType): array
    {
        if ($movementType !== '' && $movementType !== 'walking') {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT room_connections.*,
                    from_rooms.code AS from_room_code,
                    from_rooms.name AS from_room_name,
                    to_rooms.code AS to_room_code,
                    to_rooms.name AS to_room_name
             FROM room_connections
             INNER JOIN rooms AS from_rooms ON from_rooms.id = room_connections.from_room_id
             INNER JOIN rooms AS to_rooms ON to_rooms.id = room_connections.to_room_id
             ORDER BY room_connections.id ASC'
        );

        $edges = [];

        foreach ($rows as $row) {
            if ($accessibleOnly && !$this->connectionAccessible((string) ($row['connection_type'] ?? 'corridor'))) {
                continue;
            }

            $edges[] = $this->connectionEdge($row, false);

            if ((int) ($row['is_bidirectional'] ?? 1) === 1) {
                $edges[] = $this->connectionEdge($row, true);
            }
        }

        return $edges;
    }

    private function connectionEdge(array $row, bool $reverse): array
    {
        $fromRoomId = (int) ($reverse ? ($row['to_room_id'] ?? 0) : ($row['from_room_id'] ?? 0));
        $toRoomId = (int) ($reverse ? ($row['from_room_id'] ?? 0) : ($row['to_room_id'] ?? 0));
        $fromCode = (string) ($reverse ? ($row['to_room_code'] ?? '') : ($row['from_room_code'] ?? ''));
        $toCode = (string) ($reverse ? ($row['from_room_code'] ?? '') : ($row['to_room_code'] ?? ''));
        $type = (string) ($row['connection_type'] ?? 'corridor');
        $minutes = (int) ($row['walking_minutes'] ?? 1);

        return [
            'source' => 'connection',
            'connection_id' => (int) ($row['id'] ?? 0),
            'from_room_id' => $fromRoomId,
            'to_room_id' => $toRoomId,
            'duration_minutes' => $minutes,
            'distance_meters' => 0,
            'is_accessible' => $this->connectionAccessible($type) ? 1 : 0,
            'movement_type' => 'walking',
            'name' => sprintf('%s via %s', $fromCode, $type),
            'steps' => [[
                'step_order' => 1,
                'instruction' => sprintf(
                    'Move from room %s to room %s via %s.',
                    $fromCode !== '' ? $fromCode : '#' . $fromRoomId,
                    $toCode !== '' ? $toCode : '#' . $toRoomId,
                    $type
                ),
                'point_type' => 'room',
                'room_id' => $toRoomId,
                'duration_minutes' => $minutes,
            ]],
        ];
    }

    private function resolvedRouteFromSegments(int $fromRoomId, int $toRoomId, array $segments, string $movementType): Route
    {
        $fromRoom = $this->roomSummary($fromRoomId);
        $toRoom = $this->roomSummary($toRoomId);
        $preferredMovementType = $movementType !== '' ? $movementType : 'walking';
        $walking = 0;
        $car = 0;
        $publicTransport = 0;
        $hasWalking = false;
        $hasCar = false;
        $hasPublicTransport = false;
        $distanceMeters = 0;
        $steps = [];
        $segmentNames = [];
        $routeIds = [];

        foreach ($segments as $segment) {
            $segmentNames[] = (string) ($segment['name'] ?? ($segment['source'] ?? 'segment'));
            $distanceMeters += (int) ($segment['distance_meters'] ?? 0);

            $duration = (int) ($segment['duration_minutes'] ?? 0);
            $segmentMovementType = (string) ($segment['movement_type'] ?? $preferredMovementType);
            if ($segmentMovementType === 'walking') {
                $walking += $duration;
                $hasWalking = true;
            } elseif ($segmentMovementType === 'car') {
                $car += $duration;
                $hasCar = true;
            } elseif ($segmentMovementType === 'public_transport') {
                $publicTransport += $duration;
                $hasPublicTransport = true;
            }

            if (!empty($segment['route_id'])) {
                $routeIds[] = (int) $segment['route_id'];
            }

            foreach ((array) ($segment['steps'] ?? []) as $step) {
                if (!is_array($step)) {
                    continue;
                }

                $step['step_order'] = count($steps) + 1;
                $steps[] = $step;
            }
        }

        $name = sprintf(
            'Resolved route %s -> %s',
            (string) ($fromRoom['code'] ?? ('#' . $fromRoomId)),
            (string) ($toRoom['code'] ?? ('#' . $toRoomId))
        );

        return ResolvedRoute::fromArray([
            'id' => count(array_unique($routeIds)) === 1 ? $routeIds[0] : null,
            'from_room_id' => $fromRoomId,
            'to_room_id' => $toRoomId,
            'from_building_id' => $fromRoom['building_id'] ?? null,
            'to_building_id' => $toRoom['building_id'] ?? null,
            'name' => $name,
            'description' => 'Resolved from connected route and room connection segments.',
            'distance_meters' => $distanceMeters > 0 ? $distanceMeters : null,
            'walking_duration_minutes' => $hasWalking ? $walking : null,
            'car_duration_minutes' => $hasCar ? $car : null,
            'public_transport_duration_minutes' => $hasPublicTransport ? $publicTransport : null,
            'is_accessible' => $this->segmentsAccessible($segments) ? 1 : 0,
            'status' => 'active',
            'path_data' => json_encode([
                'resolved' => true,
                'movement_type' => $preferredMovementType,
                'segments' => $segmentNames,
                'segment_count' => count($segments),
                'route_ids' => array_values(array_unique($routeIds)),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'resolved_steps' => $steps,
            'resolved_segment_count' => count($segments),
        ]);
    }

    private function reverseSteps(array $steps): array
    {
        $reversed = array_reverse($steps);
        $normalized = [];

        foreach ($reversed as $index => $step) {
            if (!is_array($step)) {
                continue;
            }

            $normalized[] = [
                'step_order' => $index + 1,
                'instruction' => sprintf('Reverse: %s', (string) ($step['instruction'] ?? 'Follow the route in reverse.')),
                'point_type' => $step['point_type'] ?? 'text',
                'room_id' => $step['room_id'] ?? null,
                'floor_point_id' => $step['floor_point_id'] ?? null,
                'building_id' => $step['building_id'] ?? null,
                'duration_minutes' => (int) ($step['duration_minutes'] ?? 0),
            ];
        }

        return $normalized;
    }

    private function identityRoute(int $roomId): Route
    {
        $room = $this->roomSummary($roomId);

        return ResolvedRoute::fromArray([
            'from_room_id' => $roomId,
            'to_room_id' => $roomId,
            'from_building_id' => $room['building_id'] ?? null,
            'to_building_id' => $room['building_id'] ?? null,
            'name' => sprintf('Stay in room %s', (string) ($room['code'] ?? ('#' . $roomId))),
            'description' => 'Origin and destination are the same room.',
            'walking_duration_minutes' => 0,
            'is_accessible' => 1,
            'status' => 'active',
            'resolved_steps' => [],
            'resolved_segment_count' => 0,
        ]);
    }

    private function roomSummary(int $roomId): ?array
    {
        return $this->db()->fetch(
            'SELECT rooms.id, rooms.code, rooms.name, floors.building_id
             FROM rooms
             LEFT JOIN floors ON floors.id = rooms.floor_id
             WHERE rooms.id = :id
             LIMIT 1',
            ['id' => $roomId]
        );
    }

    private function compareRoutes(Route $left, Route $right, string $movementType): int
    {
        $leftDuration = $left->durationFor($movementType) ?? $left->durationFor() ?? PHP_INT_MAX;
        $rightDuration = $right->durationFor($movementType) ?? $right->durationFor() ?? PHP_INT_MAX;

        if ($leftDuration !== $rightDuration) {
            return $leftDuration <=> $rightDuration;
        }

        return ((int) $left->get('distance_meters', PHP_INT_MAX)) <=> ((int) $right->get('distance_meters', PHP_INT_MAX));
    }

    private function candidateKey(Route $route): string
    {
        $routeId = $route->get('id');

        return $routeId !== null
            ? 'route:' . (string) $routeId . ':' . (string) $route->get('from_room_id') . ':' . (string) $route->get('to_room_id')
            : 'resolved:' . md5(json_encode($route->path(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $route->get('name', ''));
    }

    private function connectionAccessible(string $connectionType): bool
    {
        return $connectionType !== 'stairs';
    }

    private function segmentsAccessible(array $segments): bool
    {
        foreach ($segments as $segment) {
            if ((int) ($segment['is_accessible'] ?? 1) !== 1) {
                return false;
            }
        }

        return true;
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
