<?php

declare(strict_types=1);

class EventService
{
    public function __construct(
        private readonly EventRepository $events = new EventRepository(),
        private readonly RoomRepository $rooms = new RoomRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService(),
        private readonly ?Database $database = null
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

    public function create(array $data, ?array $onlineSession = null, ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'draft';
        $data['type'] = $data['type'] ?? 'lecture';
        $data['delivery_mode'] = $data['delivery_mode'] ?? 'physical';
        $data['created_by'] = $data['created_by'] ?? $userId;
        $data = $this->validation->normalizeIntegers($data, ['created_by', 'room_id', 'students_count']);

        $errors = $this->validateEventData($data);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $event = $this->db()->transaction(function (Database $db) use ($data, $onlineSession): Event {
                $this->guardRoomBooking($db, $data, null);
                $event = $this->events->create($data);

                if ($event->requiresOnlineSession()) {
                    $this->events->upsertOnlineSession((int) $event->get('id'), $this->onlineSessionPayload($event, $onlineSession ?? []));
                }

                return $event;
            });
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
        $merged = $payload + $event->toArray();
        $errors = $this->validateEventData($merged, (int) $event->get('id'));

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $old = $event->toArray();

        try {
            $updated = $this->db()->transaction(function (Database $db) use ($id, $payload, $onlineSession): ?Event {
                $lockedEvent = $db->fetch('SELECT * FROM events WHERE id = :id LIMIT 1 FOR UPDATE', ['id' => $id]);
                if ($lockedEvent === null) {
                    return null;
                }

                $this->guardRoomBooking($db, $payload + $lockedEvent, (int) $id);
                $updated = $this->events->update($id, $payload);

                if ($updated !== null && $updated->requiresOnlineSession()) {
                    $this->events->upsertOnlineSession((int) $updated->get('id'), $this->onlineSessionPayload($updated, $onlineSession ?? []));
                }

                return $updated;
            });
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

        return $this->update($eventId, ['room_id' => $roomId, 'status' => 'scheduled'], null, $userId);
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
            }
        }

        return $errors;
    }

    private function onlineSessionPayload(Event $event, array $session): array
    {
        return [
            'platform' => $session['platform'] ?? 'Zoom',
            'url' => $session['url'] ?? (string) $event->get('online_url', ''),
            'access_key' => $session['access_key'] ?? $event->get('access_key'),
            'host_user_id' => $session['host_user_id'] ?? $event->get('created_by'),
            'starts_at' => $session['starts_at'] ?? $event->get('starts_at'),
            'ends_at' => $session['ends_at'] ?? $event->get('ends_at'),
        ];
    }

    private function guardRoomBooking(Database $db, array $data, ?int $ignoreEventId): void
    {
        $roomId = isset($data['room_id']) ? (int) $data['room_id'] : 0;
        if ($roomId <= 0) {
            return;
        }

        $room = $db->fetch(
            'SELECT id, status, capacity, covid_capacity FROM rooms WHERE id = :id LIMIT 1 FOR UPDATE',
            ['id' => $roomId]
        );

        if ($room === null) {
            throw new RuntimeException('Room not found.');
        }

        if (($room['status'] ?? 'available') !== 'available') {
            throw new RuntimeException('Room is not available.');
        }

        $effectiveCapacity = $room['covid_capacity'] ?? $room['capacity'] ?? 0;
        if ((int) $effectiveCapacity < (int) ($data['students_count'] ?? 0)) {
            throw new RuntimeException('Room capacity is insufficient.');
        }

        $params = [
            'room_id' => $roomId,
            'starts_at' => (string) ($data['starts_at'] ?? ''),
            'ends_at' => (string) ($data['ends_at'] ?? ''),
        ];

        $sql = 'SELECT id
                FROM events
                WHERE room_id = :room_id
                  AND starts_at < :ends_at
                  AND ends_at > :starts_at
                  AND status <> "cancelled"';

        if ($ignoreEventId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreEventId;
        }

        $sql .= ' LIMIT 1 FOR UPDATE';

        $conflict = $db->fetch($sql, $params);
        if ($conflict !== null) {
            throw new RuntimeException('Room is busy in the selected interval.');
        }

        $blocked = $db->fetch(
            'SELECT id
             FROM room_unavailability
             WHERE room_id = :room_id
               AND starts_at < :ends_at
               AND ends_at > :starts_at
             LIMIT 1 FOR UPDATE',
            [
                'room_id' => $roomId,
                'starts_at' => (string) ($data['starts_at'] ?? ''),
                'ends_at' => (string) ($data['ends_at'] ?? ''),
            ]
        );

        if ($blocked !== null) {
            throw new RuntimeException('Room is unavailable in the selected interval.');
        }
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
