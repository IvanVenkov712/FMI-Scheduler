<?php

declare(strict_types=1);

class RouteService
{
    public function __construct(
        private readonly RouteRepository $routes = new RouteRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function all(bool $onlyActive = false, string $movementType = ''): array
    {
        return $this->routes->all($onlyActive, $movementType);
    }

    public function find(int|string $id): ?Route
    {
        return $this->routes->find($id);
    }

    public function create(array $data, array $steps = [], ?int $userId = null): array
    {
        $data = $this->validation->normalizeIntegers($data, [
            'from_room_id', 'to_room_id', 'from_building_id', 'to_building_id', 'distance_meters', 'duration_minutes',
        ]);
        $data = $this->validation->normalizeBooleans($data, ['is_accessible']);
        $data['status'] = $data['status'] ?? 'active';
        $data['movement_type'] = $data['movement_type'] ?? 'walking';

        $errors = $this->validateRouteData($data);

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $route = $this->routes->create($data);
        if ($steps !== []) {
            $this->routes->saveSteps((int) $route->get('id'), $this->normalizeSteps($steps));
        }

        $this->auditLogs->record('create', 'route', $route->get('id'), null, $route->toArray(), $userId);

        return ['success' => true, 'route' => $route];
    }

    public function update(int|string $id, array $data, ?array $steps = null, ?int $userId = null): array
    {
        $route = $this->routes->find($id);
        if ($route === null) {
            return ['success' => false, 'errors' => ['route' => ['Route not found.']]];
        }

        $payload = $this->validation->normalizeIntegers($data, [
            'from_room_id', 'to_room_id', 'from_building_id', 'to_building_id', 'distance_meters', 'duration_minutes',
        ]);
        $payload = $this->validation->normalizeBooleans($payload, ['is_accessible']);
        $errors = $this->validateRouteData($payload + $route->toArray());

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $old = $route->toArray();
        $updated = $this->routes->update($id, $payload);

        if ($steps !== null) {
            $this->routes->saveSteps((int) $id, $this->normalizeSteps($steps));
        }

        $this->auditLogs->record('update', 'route', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'route' => $updated];
    }

    public function betweenRooms(int $fromRoomId, int $toRoomId, bool $accessibleOnly = false): array
    {
        return $this->routes->betweenRooms($fromRoomId, $toRoomId, $accessibleOnly);
    }

    public function betweenBuildings(int $fromBuildingId, int $toBuildingId, string $movementType = ''): array
    {
        return $this->routes->betweenBuildings($fromBuildingId, $toBuildingId, $movementType);
    }

    public function recommended(int $fromRoomId, int $toRoomId, bool $accessibleOnly = false): ?array
    {
        $candidates = $this->betweenRooms($fromRoomId, $toRoomId, $accessibleOnly);
        $route = $candidates[0] ?? null;

        if ($route === null) {
            return null;
        }

        return [
            'route' => $route,
            'steps' => $this->routes->steps((int) $route->get('id')),
            'explanation' => sprintf(
                'Selected route "%s" because it has the lowest travel time of %d minutes.',
                (string) $route->get('name'),
                (int) $route->get('duration_minutes', 0)
            ),
        ];
    }

    public function saveSteps(int $routeId, array $steps): void
    {
        $this->routes->saveSteps($routeId, $this->normalizeSteps($steps));
    }

    public function delete(int|string $id, ?int $userId = null): bool
    {
        $route = $this->routes->find($id);
        if ($route === null) {
            return false;
        }

        $deleted = $this->routes->delete($id);
        if ($deleted) {
            $this->auditLogs->record('delete', 'route', $id, $route->toArray(), null, $userId);
        }

        return $deleted;
    }

    private function normalizeSteps(array $steps): array
    {
        $normalized = [];

        foreach ($steps as $index => $step) {
            if (!is_array($step) || empty($step['instruction'])) {
                continue;
            }

            $normalized[] = [
                'step_order' => (int) ($step['step_order'] ?? ($index + 1)),
                'instruction' => (string) $step['instruction'],
                'point_type' => (string) ($step['point_type'] ?? 'text'),
                'room_id' => isset($step['room_id']) ? (int) $step['room_id'] : null,
                'floor_point_id' => isset($step['floor_point_id']) ? (int) $step['floor_point_id'] : null,
                'building_id' => isset($step['building_id']) ? (int) $step['building_id'] : null,
                'duration_minutes' => (int) ($step['duration_minutes'] ?? 0),
            ];
        }

        return $normalized;
    }

    private function validateRouteData(array $data): array
    {
        $result = $this->validation->validate($data, [
            'name' => 'required|string|max:190',
            'movement_type' => 'required|in:walking,car,public_transport',
            'duration_minutes' => 'required|integer|min:1',
            'distance_meters' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive,restricted',
            'is_accessible' => 'nullable|boolean',
        ]);

        $errors = $result['errors'];
        if (empty($data['from_room_id']) && empty($data['from_building_id'])) {
            $errors['from'][] = 'A route must define a start room or start building.';
        }
        if (empty($data['to_room_id']) && empty($data['to_building_id'])) {
            $errors['to'][] = 'A route must define an end room or end building.';
        }

        return $errors;
    }
}
