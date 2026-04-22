<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouteRepositoryTest extends TestCase
{
    public function testBetweenRoomsFindsComposedPathThroughRoomConnections(): void
    {
        $database = new FakeRouteRepositoryDatabase(
            routeRows: [],
            routeStepRows: [],
            roomConnectionRows: [
                [
                    'id' => 10,
                    'from_room_id' => 1,
                    'to_room_id' => 2,
                    'connection_type' => 'corridor',
                    'is_bidirectional' => 1,
                    'walking_minutes' => 3,
                    'from_room_code' => 'A101',
                    'from_room_name' => 'A101',
                    'to_room_code' => 'A102',
                    'to_room_name' => 'A102',
                ],
                [
                    'id' => 11,
                    'from_room_id' => 2,
                    'to_room_id' => 3,
                    'connection_type' => 'stairs',
                    'is_bidirectional' => 1,
                    'walking_minutes' => 4,
                    'from_room_code' => 'A102',
                    'from_room_name' => 'A102',
                    'to_room_code' => 'B201',
                    'to_room_name' => 'B201',
                ],
            ],
            roomSummaries: [
                1 => ['id' => 1, 'code' => 'A101', 'name' => 'A101', 'building_id' => 1],
                2 => ['id' => 2, 'code' => 'A102', 'name' => 'A102', 'building_id' => 1],
                3 => ['id' => 3, 'code' => 'B201', 'name' => 'B201', 'building_id' => 1],
            ]
        );

        $repository = new RouteRepository($database);
        $routes = $repository->betweenRooms(1, 3, false, 'walking');

        $this->assertNotSame([], $routes);
        $best = $routes[0];
        $this->assertSame(7, $best->durationFor('walking'));
        $this->assertSame(2, $best->path()['segment_count']);
        $this->assertCount(2, $best->get('resolved_steps'));
    }

    public function testBetweenRoomsUsesReverseDirectionForDirectRoute(): void
    {
        $database = new FakeRouteRepositoryDatabase(
            routeRows: [[
                'id' => 22,
                'from_room_id' => 1,
                'to_room_id' => 2,
                'name' => 'Main corridor',
                'distance_meters' => 120,
                'walking_duration_minutes' => 2,
                'car_duration_minutes' => null,
                'public_transport_duration_minutes' => null,
                'is_accessible' => 1,
                'status' => 'active',
            ]],
            routeStepRows: [
                22 => [[
                    'step_order' => 1,
                    'instruction' => 'Walk straight through the main corridor.',
                    'point_type' => 'text',
                    'duration_minutes' => 2,
                ]],
            ],
            roomConnectionRows: [],
            roomSummaries: [
                1 => ['id' => 1, 'code' => 'A101', 'name' => 'A101', 'building_id' => 1],
                2 => ['id' => 2, 'code' => 'A102', 'name' => 'A102', 'building_id' => 1],
            ]
        );

        $repository = new RouteRepository($database);
        $routes = $repository->betweenRooms(2, 1, false, 'walking');

        $this->assertNotSame([], $routes);
        $best = $routes[0];
        $this->assertSame(2, $best->durationFor('walking'));
        $this->assertSame(22, $best->get('id'));
        $this->assertSame(2, $best->get('from_room_id'));
        $this->assertSame(1, $best->get('to_room_id'));
    }
}

final class FakeRouteRepositoryDatabase extends Database
{
    public function __construct(
        private readonly array $routeRows,
        private readonly array $routeStepRows,
        private readonly array $roomConnectionRows,
        private readonly array $roomSummaries
    ) {
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        if (str_contains($sql, 'FROM routes')) {
            return $this->filterRoutes($sql, $params);
        }

        if (str_contains($sql, 'FROM route_steps')) {
            return $this->routeStepRows[(int) ($params['route_id'] ?? 0)] ?? [];
        }

        if (str_contains($sql, 'FROM room_connections')) {
            return $this->roomConnectionRows;
        }

        return [];
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        if (str_contains($sql, 'FROM rooms')) {
            return $this->roomSummaries[(int) ($params['id'] ?? 0)] ?? null;
        }

        return null;
    }

    private function filterRoutes(string $sql, array $params): array
    {
        $rows = $this->routeRows;

        if (isset($params['from_room_id'], $params['to_room_id'])) {
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => (int) ($row['from_room_id'] ?? 0) === (int) $params['from_room_id']
                    && (int) ($row['to_room_id'] ?? 0) === (int) $params['to_room_id']
            ));
        }

        if (isset($params['is_accessible'])) {
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => (int) ($row['is_accessible'] ?? 0) === (int) $params['is_accessible']
            ));
        }

        return $rows;
    }
}
