<?php

declare(strict_types=1);

class FloorService
{
    public function __construct(
        private readonly FloorRepository $floors = new FloorRepository(),
        private readonly BuildingRepository $buildings = new BuildingRepository(),
        private readonly AsciiPlanParser $asciiParser = new AsciiPlanParser(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function byBuilding(int $buildingId, bool $onlyActive = false): array
    {
        return $this->floors->byBuilding($buildingId, $onlyActive);
    }

    public function find(int|string $id): ?Floor
    {
        return $this->floors->find($id);
    }

    public function create(array $data, ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'active';
        $data = $this->validation->normalizeIntegers($data, [
            'building_id', 'number', 'capacity', 'width_units', 'height_units',
        ]);

        $result = $this->validation->validate($data, [
            'building_id' => 'required|integer|min:1',
            'number' => 'required|integer',
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive,maintenance',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if ($this->buildings->find((int) $data['building_id']) === null) {
            return ['success' => false, 'errors' => ['building_id' => ['Building not found.']]];
        }

        foreach ($this->floors->byBuilding((int) $data['building_id']) as $existingFloor) {
            if ((int) $existingFloor->get('number') === (int) $data['number']) {
                return ['success' => false, 'errors' => ['number' => ['Floor number already exists in this building.']]];
            }
        }

        $floor = $this->floors->create($data);
        $this->auditLogs->record('create', 'floor', $floor->get('id'), null, $floor->toArray(), $userId);

        return ['success' => true, 'floor' => $floor];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $floor = $this->floors->find($id);
        if ($floor === null) {
            return ['success' => false, 'errors' => ['floor' => ['Floor not found.']]];
        }

        $payload = $this->validation->normalizeIntegers($data, [
            'building_id', 'number', 'capacity', 'width_units', 'height_units',
        ]);

        $result = $this->validation->validate($payload + $floor->toArray(), [
            'building_id' => 'required|integer|min:1',
            'number' => 'required|integer',
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive,maintenance',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        $targetBuildingId = (int) ($payload['building_id'] ?? $floor->get('building_id'));
        $targetNumber = (int) ($payload['number'] ?? $floor->get('number'));

        foreach ($this->floors->byBuilding($targetBuildingId) as $existingFloor) {
            if ((int) $existingFloor->get('id') !== (int) $floor->get('id') && (int) $existingFloor->get('number') === $targetNumber) {
                return ['success' => false, 'errors' => ['number' => ['Floor number already exists in this building.']]];
            }
        }

        $old = $floor->toArray();
        $updated = $this->floors->update($id, $payload);
        $this->auditLogs->record('update', 'floor', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'floor' => $updated];
    }

    public function savePlan(int $floorId, string $asciiPlan, array $points = [], ?string $planData = null, ?int $userId = null): array
    {
        $floor = $this->floors->find($floorId);
        if ($floor === null) {
            return ['success' => false, 'errors' => ['floor' => ['Floor not found.']]];
        }

        $parsed = $this->asciiParser->parse($asciiPlan);
        $payload = [
            'ascii_plan' => $asciiPlan,
            'width_units' => $parsed['width'],
            'height_units' => $parsed['height'],
        ];

        if ($planData !== null) {
            $payload['plan_data'] = $planData;
        }

        $old = $floor->toArray();
        $updated = $this->floors->update($floorId, $payload);
        $this->floors->savePoints($floorId, $points);
        $this->auditLogs->record('update_plan', 'floor', $floorId, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'floor' => $updated, 'parsed_plan' => $parsed];
    }

    public function importAsciiPlan(int $floorId, string $filePath, ?int $userId = null): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return ['success' => false, 'errors' => ['file' => ['ASCII plan file could not be read.']]];
        }

        return $this->savePlan($floorId, $content, [], null, $userId);
    }

    public function rooms(int $floorId, bool $onlyAvailable = false): array
    {
        return $this->floors->rooms($floorId, $onlyAvailable);
    }

    public function points(int $floorId): array
    {
        return $this->floors->points($floorId);
    }

    public function delete(int|string $id, ?int $userId = null): bool
    {
        $floor = $this->floors->find($id);
        if ($floor === null) {
            return false;
        }

        $deleted = $this->floors->delete($id);
        if ($deleted) {
            $this->auditLogs->record('delete', 'floor', $id, $floor->toArray(), null, $userId);
        }

        return $deleted;
    }
}
