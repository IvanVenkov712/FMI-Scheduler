<?php

declare(strict_types=1);

class RoomService
{
    public function __construct(
        private readonly RoomRepository $rooms = new RoomRepository(),
        private readonly EventRepository $events = new EventRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function byFloor(int $floorId, bool $onlyAvailable = false): array
    {
        return $this->rooms->byFloor($floorId, $onlyAvailable);
    }

    public function find(int|string $id): ?Room
    {
        return $this->rooms->find($id);
    }

    public function listing(array $filters = []): array
    {
        return $this->rooms->listing($filters);
    }

    public function roomTypes(): array
    {
        return $this->rooms->roomTypes();
    }

    public function create(array $data, array $resources = [], ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'available';
        $data = $this->validation->normalizeIntegers($data, [
            'floor_id', 'room_type_id', 'capacity', 'covid_capacity', 'x', 'y', 'width', 'height',
        ]);

        $result = $this->validation->validate($data, [
            'room_type_id' => 'required|integer|min:1',
            'floor_id' => 'nullable|integer|min:1',
            'code' => 'required|string|max:60',
            'name' => 'required|string|max:160',
            'capacity' => 'nullable|integer|min:0',
            'covid_capacity' => 'nullable|integer|min:0',
            'online_url' => 'nullable|url',
            'status' => 'nullable|in:available,occupied,maintenance,unavailable',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if ($this->rooms->findByCode((string) $data['code']) !== null) {
            return ['success' => false, 'errors' => ['code' => ['Room code already exists.']]];
        }

        $room = $this->rooms->create($data);

        if ($resources !== []) {
            $this->rooms->saveResources((int) $room->get('id'), $this->normalizeResources($resources));
        }

        $this->auditLogs->record('create', 'room', $room->get('id'), null, $room->toArray(), $userId);

        return ['success' => true, 'room' => $room];
    }

    public function update(int|string $id, array $data, ?array $resources = null, ?int $userId = null): array
    {
        $room = $this->rooms->find($id);
        if ($room === null) {
            return ['success' => false, 'errors' => ['room' => ['Room not found.']]];
        }

        $payload = $this->validation->normalizeIntegers($data, [
            'floor_id', 'room_type_id', 'capacity', 'covid_capacity', 'x', 'y', 'width', 'height',
        ]);

        $result = $this->validation->validate($payload + $room->toArray(), [
            'room_type_id' => 'required|integer|min:1',
            'floor_id' => 'nullable|integer|min:1',
            'code' => 'required|string|max:60',
            'name' => 'required|string|max:160',
            'capacity' => 'nullable|integer|min:0',
            'covid_capacity' => 'nullable|integer|min:0',
            'online_url' => 'nullable|url',
            'status' => 'nullable|in:available,occupied,maintenance,unavailable',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if (isset($payload['code'])) {
            $existing = $this->rooms->findByCode((string) $payload['code']);
            if ($existing !== null && (int) $existing->get('id') !== (int) $room->get('id')) {
                return ['success' => false, 'errors' => ['code' => ['Room code already exists.']]];
            }
        }

        $old = $room->toArray();
        $updated = $this->rooms->update($id, $payload);

        if ($resources !== null) {
            $this->rooms->saveResources((int) $id, $this->normalizeResources($resources));
        }

        $this->auditLogs->record('update', 'room', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'room' => $updated];
    }

    public function availableBetween(
        int $capacity,
        string $startsAt,
        string $endsAt,
        string $deliveryMode = 'physical',
        array $requiredResources = [],
        bool $preferCovidCapacity = true
    ): array {
        return $this->rooms->availableBetween(
            $capacity,
            $startsAt,
            $endsAt,
            $deliveryMode,
            $this->normalizeRequiredResources($requiredResources),
            $preferCovidCapacity
        );
    }

    public function availabilityOverview(int $roomId, string $from, string $to): array
    {
        $room = $this->rooms->find($roomId);
        if ($room === null) {
            throw new RuntimeException('Room not found.');
        }

        return [
            'room' => $room,
            'events' => $room->events($from, $to),
            'unavailability' => $room->unavailability($from, $to),
            'resources' => $room->resources(),
            'connections' => $this->rooms->connections($roomId),
        ];
    }

    public function canHostEvent(int $roomId, string $startsAt, string $endsAt, int $studentsCount, array $requiredResources = []): bool
    {
        $room = $this->rooms->find($roomId);
        if ($room === null || !$room->isAvailableBetween($startsAt, $endsAt)) {
            return false;
        }

        if ($room->effectiveCapacity() < $studentsCount) {
            return false;
        }

        return ResourceRequirementHelper::satisfies(
            $room->resources(),
            $this->normalizeRequiredResources($requiredResources)
        );
    }

    public function blockRoom(array $data, ?int $userId = null): array
    {
        $data = $this->validation->normalizeIntegers($data, ['room_id', 'created_by']);
        $errors = $this->validation->validateDateRange((string) ($data['starts_at'] ?? ''), (string) ($data['ends_at'] ?? ''));

        $result = $this->validation->validate($data, [
            'room_id' => 'required|integer|min:1',
            'reason' => 'required|string|max:190',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
        ]);

        if (!$result['valid'] || $errors !== []) {
            return ['success' => false, 'errors' => array_merge_recursive($result['errors'], $errors)];
        }

        $data['created_by'] = $data['created_by'] ?? $userId;
        $id = $this->rooms->createUnavailability($data);
        $this->auditLogs->record('block', 'room', $data['room_id'], null, $data, $userId);

        return ['success' => true, 'unavailability_id' => $id];
    }

    public function delete(int|string $id, ?int $userId = null): bool
    {
        $room = $this->rooms->find($id);
        if ($room === null) {
            return false;
        }

        $deleted = $this->rooms->delete($id);
        if ($deleted) {
            $this->auditLogs->record('delete', 'room', $id, $room->toArray(), null, $userId);
        }

        return $deleted;
    }

    private function normalizeResources(array $resources): array
    {
        $normalized = [];

        foreach ($resources as $resource) {
            if (!is_array($resource) || empty($resource['resource_id'])) {
                continue;
            }

            $normalized[] = [
                'resource_id' => (int) $resource['resource_id'],
                'quantity' => (int) ($resource['quantity'] ?? 1),
                'status' => (string) ($resource['status'] ?? 'available'),
                'notes' => $resource['notes'] ?? null,
            ];
        }

        return $normalized;
    }

    private function normalizeRequiredResources(array $requiredResources): array
    {
        return ResourceRequirementHelper::normalize($requiredResources);
    }
}
