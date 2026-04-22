<?php

declare(strict_types=1);

class EventService
{
    public function __construct(
        private readonly EventRepository $events = new EventRepository(),
        private readonly RoomRepository $rooms = new RoomRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function all(array $filters = []): array
    {
        return $this->events->all($filters);
    }

    public function upcoming(int $limit = 10): array
    {
        return $this->events->upcoming($limit);
    }

    public function find(int|string $id): ?Event
    {
        return $this->events->find($id);
    }

    public function hasRoomConflict(int $roomId, string $startsAt, string $endsAt, ?int $ignoreEventId = null): bool
    {
        if ($roomId <= 0 || $startsAt === '' || $endsAt === '') {
            return false;
        }

        return $this->events->overlapsForRoom($roomId, $startsAt, $endsAt, $ignoreEventId) !== [];
    }

    public function create(array $data, ?array $onlineSession = null, ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'draft';
        $data['type'] = $data['type'] ?? 'lecture';
        $data['delivery_mode'] = $data['delivery_mode'] ?? 'physical';
        $data['created_by'] = $data['created_by'] ?? $userId;
        $data = $this->validation->normalizeIntegers($data, ['created_by', 'room_id', 'students_count']);
        $onlineSession = $this->normalizeOnlineSession($onlineSession);

        $errors = $this->validateEventData($data);
        $errors = array_merge_recursive($errors, $this->validateOnlineSession($data, $onlineSession));
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $event = $this->events->createWithOnlineSession($data, $onlineSession === null ? null : $this->onlineSessionPayload(new Event($data), $onlineSession));
        } catch (RuntimeException $exception) {
            return ['success' => false, 'errors' => ['room_id' => [$exception->getMessage()]]];
        }

        $this->auditLogs->record('create', 'event', $event->get('id'), null, $event->toArray(), $userId);

        return ['success' => true, 'event' => $event];
    }

    public function update(int|string $id, array $data, ?array $onlineSession = null, ?int $userId = null): array
    {
        $event = $this->events->find($id);
        if ($event === null) {
            return ['success' => false, 'errors' => ['event' => ['Event not found.']]];
        }

        $payload = $this->validation->normalizeIntegers($data, ['created_by', 'room_id', 'students_count']);
        $onlineSession = $this->normalizeOnlineSession($onlineSession);
        $existingSession = $event->onlineSession()?->toArray() ?? [];
        $merged = $payload + $event->toArray();
        $errors = $this->validateEventData($merged, (int) $event->get('id'));
        $errors = array_merge_recursive($errors, $this->validateOnlineSession($merged, $onlineSession, $existingSession));

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $old = $event->toArray();

        try {
            $updated = $this->events->updateWithOnlineSession(
                $id,
                $payload,
                $this->onlineSessionPayload($event, $onlineSession ?? [], $existingSession)
            );
        } catch (RuntimeException $exception) {
            return ['success' => false, 'errors' => ['room_id' => [$exception->getMessage()]]];
        }

        $this->auditLogs->record('update', 'event', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'event' => $updated];
    }

    public function cancel(int|string $id, ?int $userId = null): array
    {
        return $this->update($id, ['status' => 'cancelled'], null, $userId);
    }

    public function delete(int|string $id, ?int $userId = null): bool
    {
        $event = $this->events->find($id);
        if ($event === null) {
            return false;
        }

        $deleted = $this->events->delete($id);
        if ($deleted) {
            $this->auditLogs->record('delete', 'event', $id, $event->toArray(), null, $userId);
        }

        return $deleted;
    }

    public function assignRoom(int $eventId, int $roomId, ?int $userId = null): array
    {
        $event = $this->events->find($eventId);
        if ($event === null) {
            return ['success' => false, 'errors' => ['event' => ['Event not found.']]];
        }

        $room = $this->rooms->find($roomId);
        if ($room === null) {
            return ['success' => false, 'errors' => ['room' => ['Room not found.']]];
        }

        $studentsCount = (int) $event->get('students_count', 0);
        if ($room->effectiveCapacity() < $studentsCount) {
            return ['success' => false, 'errors' => ['room' => ['Room capacity is insufficient.']]];
        }

        $overlaps = $this->events->overlapsForRoom(
            $roomId,
            (string) $event->get('starts_at'),
            (string) $event->get('ends_at'),
            (int) $event->get('id')
        );

        if ($overlaps !== []) {
            return ['success' => false, 'errors' => ['room' => ['Room is busy in the selected interval.']]];
        }

        if (!$this->roomSatisfiesRequiredResources($room, $event->requiredResources())) {
            return ['success' => false, 'errors' => ['room' => ['Room resources are insufficient for the event requirements.']]];
        }

        return $this->update($eventId, ['room_id' => $roomId, 'status' => 'scheduled'], null, $userId);
    }

    public function validateRequestOnlineSession(array $session): array
    {
        return $this->validateOnlineSession(
            ['delivery_mode' => 'online'],
            $session
        );
    }

    public function importOnlineSession(array $data, ?int $userId = null): array
    {
        $data = $this->validation->normalizeIntegers($data, ['event_id', 'host_user_id']);
        $result = $this->validation->validate($data, [
            'event_id' => 'required|integer|min:1',
        ]);

        $errors = $result['errors'];
        $errors = array_merge_recursive($errors, $this->validateRequestOnlineSession($data));
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $eventId = (int) ($data['event_id'] ?? 0);
        $event = $this->events->find($eventId);
        if ($event === null) {
            return ['success' => false, 'errors' => ['event_id' => ['Event not found.']]];
        }

        $existing = $this->events->findOnlineSession($eventId);
        $session = $this->events->upsertOnlineSession($eventId, $data);
        $this->auditLogs->record(
            $existing === null ? 'create' : 'update',
            'online_session',
            $session->get('id'),
            $existing?->toArray(),
            $session->toArray(),
            $userId
        );

        return ['success' => true, 'online_session' => $session];
    }

    private function validateEventData(array $data, ?int $ignoreEventId = null): array
    {
        $result = $this->validation->validate($data, [
            'title' => 'required|string|max:190',
            'type' => 'required|in:exam,lecture,exercise,consultation,other',
            'students_count' => 'nullable|integer|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
            'delivery_mode' => 'required|in:physical,online,hybrid',
            'required_resources' => 'nullable|array',
            'status' => 'required|in:draft,requested,scheduled,cancelled,completed',
            'room_id' => 'nullable|integer|min:1',
        ]);

        $errors = $result['errors'];
        $errors = array_merge_recursive(
            $errors,
            $this->validation->validateDateRange((string) ($data['starts_at'] ?? ''), (string) ($data['ends_at'] ?? ''))
        );

        if (!empty($data['room_id'])) {
            $room = $this->rooms->find((int) $data['room_id']);
            if ($room === null) {
                $errors['room_id'][] = 'Room not found.';
            } else {
                if ((int) $room->effectiveCapacity() < (int) ($data['students_count'] ?? 0)) {
                    $errors['room_id'][] = 'Room capacity is insufficient.';
                }

                $overlaps = $this->events->overlapsForRoom(
                    (int) $data['room_id'],
                    (string) $data['starts_at'],
                    (string) $data['ends_at'],
                    $ignoreEventId
                );

                if ($overlaps !== []) {
                    $errors['room_id'][] = 'Room is busy in the selected interval.';
                }

                if (!$this->roomSatisfiesRequiredResources($room, (array) ($data['required_resources'] ?? []))) {
                    $errors['room_id'][] = 'Room resources are insufficient for the event requirements.';
                }
            }
        }

        return $errors;
    }

    private function onlineSessionPayload(Event $event, array $session, array $existingSession = []): array
    {
        return [
            'platform' => $session['platform'] ?? $existingSession['platform'] ?? 'Zoom',
            'url' => $session['url'] ?? $existingSession['url'] ?? (string) $event->get('online_url', ''),
            'access_key' => $session['access_key'] ?? $existingSession['access_key'] ?? $event->get('access_key'),
            'access_password' => $session['access_password'] ?? $existingSession['access_password'] ?? null,
            'host_user_id' => $session['host_user_id'] ?? $existingSession['host_user_id'] ?? $event->get('created_by'),
            'moderator_name' => $session['moderator_name'] ?? $existingSession['moderator_name'] ?? null,
            'access_instructions' => $session['access_instructions'] ?? $existingSession['access_instructions'] ?? null,
            'starts_at' => $session['starts_at'] ?? $existingSession['starts_at'] ?? $event->get('starts_at'),
            'ends_at' => $session['ends_at'] ?? $existingSession['ends_at'] ?? $event->get('ends_at'),
        ];
    }

    private function normalizeOnlineSession(?array $session): array
    {
        if (!is_array($session)) {
            return [];
        }

        return $this->validation->normalizeIntegers($session, ['host_user_id']);
    }

    private function validateOnlineSession(array $eventData, array $session, array $existingSession = []): array
    {
        $deliveryMode = (string) ($eventData['delivery_mode'] ?? 'physical');
        if (!in_array($deliveryMode, ['online', 'hybrid'], true)) {
            return [];
        }

        $session = $this->onlineSessionPayload(new Event($eventData), $session, $existingSession);
        $result = $this->validation->validate($session, [
            'platform' => 'required|string|max:100',
            'url' => 'nullable|string|max:500',
            'access_key' => 'nullable|string|max:255',
            'access_password' => 'nullable|string|max:255',
            'host_user_id' => 'nullable|integer|min:1',
            'moderator_name' => 'nullable|string|max:160',
            'access_instructions' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
        ]);

        $errors = $result['errors'];
        $errors = array_merge_recursive(
            $errors,
            $this->validation->validateDateRange((string) ($session['starts_at'] ?? ''), (string) ($session['ends_at'] ?? ''), 'online_starts_at', 'online_ends_at')
        );

        if (($session['url'] ?? '') === '' && ($session['access_key'] ?? '') === '') {
            $errors['online_access'][] = 'Online events require at least a join URL or an access key.';
        }

        return $errors;
    }

    private function roomSatisfiesRequiredResources(Room $room, array $requiredResources): bool
    {
        $requirements = ResourceRequirementHelper::normalize($requiredResources);

        if ($requirements === []) {
            return true;
        }

        return ResourceRequirementHelper::satisfies($room->resources(), $requirements);
    }
}
