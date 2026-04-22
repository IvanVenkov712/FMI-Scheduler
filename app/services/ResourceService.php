<?php

declare(strict_types=1);

class ResourceService
{
    public function __construct(
        private readonly ResourceRepository $resources = new ResourceRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function all(bool $onlyConsumable = false): array
    {
        return $this->resources->all($onlyConsumable);
    }

    public function find(int|string $id): ?Resource
    {
        return $this->resources->find($id);
    }

    public function listing(array $filters = []): array
    {
        return $this->resources->listing($filters);
    }

    public function create(array $data, ?int $userId = null): array
    {
        $data = $this->validation->normalizeBooleans($data, ['is_consumable']);
        $result = $this->validation->validate($data, [
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:80',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:60',
            'is_consumable' => 'nullable|boolean',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if ($this->resources->findBySlug((string) $data['slug']) !== null) {
            return ['success' => false, 'errors' => ['slug' => ['Resource slug already exists.']]];
        }

        $resource = $this->resources->create($data);
        $this->auditLogs->record('create', 'resource', $resource->get('id'), null, $resource->toArray(), $userId);

        return ['success' => true, 'resource' => $resource];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $resource = $this->resources->find($id);
        if ($resource === null) {
            return ['success' => false, 'errors' => ['resource' => ['Resource not found.']]];
        }

        $payload = $this->validation->normalizeBooleans($data, ['is_consumable']);
        $result = $this->validation->validate($payload + $resource->toArray(), [
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:80',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:60',
            'is_consumable' => 'nullable|boolean',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if (isset($payload['slug'])) {
            $existing = $this->resources->findBySlug((string) $payload['slug']);
            if ($existing !== null && (int) $existing->get('id') !== (int) $resource->get('id')) {
                return ['success' => false, 'errors' => ['slug' => ['Resource slug already exists.']]];
            }
        }

        $old = $resource->toArray();
        $updated = $this->resources->update($id, $payload);
        $this->auditLogs->record('update', 'resource', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'resource' => $updated];
    }

    public function assignToRoom(int $roomId, int $resourceId, int $quantity, string $status = 'available', ?string $notes = null): void
    {
        $this->resources->saveRoomResource($roomId, $resourceId, $quantity, $status, $notes);
    }

    public function roomResources(int $resourceId): array
    {
        return $this->resources->roomResources($resourceId);
    }

    public function resourcesForRoom(int|string $roomId): array
    {
        return $this->resources->resourcesForRoom($roomId);
    }

    public function requestResource(array $data, ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'pending';
        $data['requested_by'] = $data['requested_by'] ?? $userId;
        $data = $this->validation->normalizeIntegers($data, ['requested_by', 'event_id', 'room_id', 'resource_id', 'quantity', 'reviewed_by']);

        $result = $this->validation->validate($data, [
            'resource_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|in:pending,approved,rejected,fulfilled,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        $id = $this->resources->createResourceRequest($data);
        $this->auditLogs->record('create', 'resource_request', $id, null, $data, $userId);

        return ['success' => true, 'resource_request_id' => $id];
    }

    public function importResourceRequest(array $data, ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'pending';
        $data['requested_by'] = $data['requested_by'] ?? $userId;
        $data = $this->validation->normalizeIntegers($data, ['requested_by', 'event_id', 'room_id', 'resource_id', 'quantity', 'reviewed_by']);

        $result = $this->validation->validate($data, [
            'resource_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|in:pending,approved,rejected,fulfilled,cancelled',
            'notes' => 'nullable|string',
            'reviewed_at' => 'nullable|date',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        $requestId = isset($data['id']) && (int) $data['id'] > 0 ? (int) $data['id'] : null;
        if ($requestId === null) {
            $id = $this->resources->createResourceRequest($data);
            $created = $this->resources->findResourceRequest($id);
            $this->auditLogs->record('create', 'resource_request', $id, null, $created, $userId);

            return ['success' => true, 'resource_request' => $created];
        }

        $existing = $this->resources->findResourceRequest($requestId);
        if ($existing === null) {
            return ['success' => false, 'errors' => ['resource_request' => ['Resource request not found.']]];
        }

        $payload = $data;
        unset($payload['id'], $payload['created_at'], $payload['updated_at']);

        $this->resources->updateResourceRequest($requestId, $payload);
        $updated = $this->resources->findResourceRequest($requestId);
        $this->auditLogs->record('update', 'resource_request', $requestId, $existing, $updated, $userId);

        return ['success' => true, 'resource_request' => $updated];
    }

    public function reviewRequest(int|string $id, string $status, ?int $reviewedBy = null): bool
    {
        $updated = $this->resources->reviewResourceRequest($id, $status, $reviewedBy);

        if ($updated > 0) {
            $this->auditLogs->record('review', 'resource_request', $id, null, ['status' => $status], $reviewedBy);
        }

        return $updated > 0;
    }

    public function resourceRequests(array $filters = []): array
    {
        return $this->resources->resourceRequests($filters);
    }

    public function delete(int|string $id, ?int $userId = null): bool
    {
        $resource = $this->resources->find($id);
        if ($resource === null) {
            return false;
        }

        $deleted = $this->resources->delete($id);
        if ($deleted) {
            $this->auditLogs->record('delete', 'resource', $id, $resource->toArray(), null, $userId);
        }

        return $deleted;
    }
}
