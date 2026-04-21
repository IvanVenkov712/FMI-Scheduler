<?php

declare(strict_types=1);

class BuildingService
{
    public function __construct(
        private readonly BuildingRepository $buildings = new BuildingRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function all(bool $onlyActive = false): array
    {
        return $this->buildings->all($onlyActive);
    }

    public function statistics(): array
    {
        return $this->buildings->withStatistics();
    }

    public function find(int|string $id): ?Building
    {
        return $this->buildings->find($id);
    }

    public function create(array $data, ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'active';
        $data['orientation'] = $data['orientation'] ?? 'east';
        $data = $this->validation->normalizeIntegers($data, ['floors_count']);

        $result = $this->validation->validate($data, [
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:160',
            'address' => 'nullable|string|max:255',
            'floors_count' => 'nullable|integer|min:0',
            'orientation' => 'nullable|in:east,west,north,south',
            'status' => 'nullable|in:active,inactive,maintenance',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if ($this->buildings->findByCode((string) $data['code']) !== null) {
            return ['success' => false, 'errors' => ['code' => ['Building code already exists.']]];
        }

        $building = $this->buildings->create($data);
        $this->auditLogs->record('create', 'building', $building->get('id'), null, $building->toArray(), $userId);

        return ['success' => true, 'building' => $building];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $building = $this->buildings->find($id);
        if ($building === null) {
            return ['success' => false, 'errors' => ['building' => ['Building not found.']]];
        }

        $data = $this->validation->normalizeIntegers($data, ['floors_count']);
        $result = $this->validation->validate($data + $building->toArray(), [
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:160',
            'address' => 'nullable|string|max:255',
            'floors_count' => 'nullable|integer|min:0',
            'orientation' => 'nullable|in:east,west,north,south',
            'status' => 'nullable|in:active,inactive,maintenance',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if (isset($data['code'])) {
            $existing = $this->buildings->findByCode((string) $data['code']);
            if ($existing !== null && (int) $existing->get('id') !== (int) $building->get('id')) {
                return ['success' => false, 'errors' => ['code' => ['Building code already exists.']]];
            }
        }

        $old = $building->toArray();
        $updated = $this->buildings->update($id, $data);
        $this->auditLogs->record('update', 'building', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'building' => $updated];
    }

    public function delete(int|string $id, ?int $userId = null): bool
    {
        $building = $this->buildings->find($id);
        if ($building === null) {
            return false;
        }

        $deleted = $this->buildings->delete($id);
        if ($deleted) {
            $this->auditLogs->record('delete', 'building', $id, $building->toArray(), null, $userId);
        }

        return $deleted;
    }

    public function floors(int $buildingId, bool $onlyActive = false): array
    {
        return $this->buildings->floors($buildingId, $onlyActive);
    }

    public function verticalView(int|string $id, string $orientation = ''): ?string
    {
        return $this->buildings->find($id)?->verticalView($orientation);
    }
}
