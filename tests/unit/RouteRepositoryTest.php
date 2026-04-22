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

    public function testBetweenBuildingsFindsComposedPathThroughIntermediateBuilding(): void
    {
        $database = new FakeRouteRepositoryDatabase(
            routeRows: [
                [
                    'id' => 31,
                    'from_building_id' => 1,
                    'to_building_id' => 2,
                    'name' => 'Campus shuttle A-B',
                    'distance_meters' => 600,
                    'walking_duration_minutes' => 8,
                    'car_duration_minutes' => 2,
                    'public_transport_duration_minutes' => 4,
                    'is_accessible' => 1,
                    'status' => 'active',
                ],
                [
                    'id' => 32,
                    'from_building_id' => 2,
                    'to_building_id' => 3,
                    'name' => 'Campus shuttle B-C',
                    'distance_meters' => 900,
                    'walking_duration_minutes' => 12,
                    'car_duration_minutes' => 3,
                    'public_transport_duration_minutes' => 5,
                    'is_accessible' => 1,
                    'status' => 'active',
                ],
            ],
            routeStepRows: [
                31 => [[
                    'step_order' => 1,
                    'instruction' => 'Move from building A to building B.',
                    'point_type' => 'building',
                    'building_id' => 2,
                    'duration_minutes' => 4,
                ]],
                32 => [[
                    'step_order' => 1,
                    'instruction' => 'Move from building B to building C.',
                    'point_type' => 'building',
                    'building_id' => 3,
                    'duration_minutes' => 5,
                ]],
            ],
            roomConnectionRows: [],
            roomSummaries: [],
            buildingSummaries: [
                1 => ['id' => 1, 'code' => 'A', 'name' => 'Building A'],
                2 => ['id' => 2, 'code' => 'B', 'name' => 'Building B'],
                3 => ['id' => 3, 'code' => 'C', 'name' => 'Building C'],
            ]
        );

        $repository = new RouteRepository($database);
        $routes = $repository->betweenBuildings(1, 3, 'public_transport');

        $this->assertNotSame([], $routes);
        $best = $routes[0];
        $this->assertSame(9, $best->durationFor('public_transport'));
        $this->assertSame(2, $best->path()['segment_count']);
        $this->assertCount(2, $best->get('resolved_steps'));
    }

    public function testBetweenBuildingsUsesReverseDirectionForBuildingRoute(): void
    {
        $database = new FakeRouteRepositoryDatabase(
            routeRows: [[
                'id' => 44,
                'from_building_id' => 1,
                'to_building_id' => 2,
                'name' => 'Bridge A-B',
                'distance_meters' => 250,
                'walking_duration_minutes' => 5,
                'car_duration_minutes' => null,
                'public_transport_duration_minutes' => null,
                'is_accessible' => 1,
                'status' => 'active',
            ]],
            routeStepRows: [
                44 => [[
                    'step_order' => 1,
                    'instruction' => 'Cross the bridge from A to B.',
                    'point_type' => 'building',
                    'building_id' => 2,
                    'duration_minutes' => 5,
                ]],
            ],
            roomConnectionRows: [],
            roomSummaries: [],
            buildingSummaries: [
                1 => ['id' => 1, 'code' => 'A', 'name' => 'Building A'],
                2 => ['id' => 2, 'code' => 'B', 'name' => 'Building B'],
            ]
        );

        $repository = new RouteRepository($database);
        $routes = $repository->betweenBuildings(2, 1, 'walking');

        $this->assertNotSame([], $routes);
        $best = $routes[0];
        $this->assertSame(5, $best->durationFor('walking'));
        $this->assertSame(2, $best->get('from_building_id'));
        $this->assertSame(1, $best->get('to_building_id'));
    }
}

final class FakeRouteRepositoryDatabase extends Database
{
    public function __construct(
        private readonly array $routeRows,
        private readonly array $routeStepRows,
        private readonly array $roomConnectionRows,
        private readonly array $roomSummaries,
        private readonly array $buildingSummaries = []
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

        if (str_contains($sql, 'FROM buildings')) {
            return $this->buildingSummaries[(int) ($params['id'] ?? 0)] ?? null;
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

        if (isset($params['from_building_id'], $params['to_building_id'])) {
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => (int) ($row['from_building_id'] ?? 0) === (int) $params['from_building_id']
                    && (int) ($row['to_building_id'] ?? 0) === (int) $params['to_building_id']
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
