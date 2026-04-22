<?php

declare(strict_types=1);

class EventRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?Event
    {
        return (new GenericModelRepository($this->database))->find(Event::class, $id);
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM events WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= ' AND type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['delivery_mode'])) {
            $sql .= ' AND delivery_mode = :delivery_mode';
            $params['delivery_mode'] = $filters['delivery_mode'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= ' AND room_id = :room_id';
            $params['room_id'] = (int) $filters['room_id'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= ' AND created_by = :created_by';
            $params['created_by'] = (int) $filters['created_by'];
        }

        if (!empty($filters['starts_from'])) {
            $sql .= ' AND ends_at >= :starts_from';
            $params['starts_from'] = $filters['starts_from'];
        }

        if (!empty($filters['ends_to'])) {
            $sql .= ' AND starts_at <= :ends_to';
            $params['ends_to'] = $filters['ends_to'];
        }

        $sql .= ' ORDER BY starts_at ASC, id ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function create(array $data): Event
    {
        if (isset($data['required_resources']) && is_array($data['required_resources'])) {
            $data['required_resources'] = json_encode($data['required_resources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $event = new Event($data);
        $event->save();

        return $event;
    }

    public function createWithOnlineSession(array $data, ?array $onlineSession = null): Event
    {
        return $this->db()->transaction(function (Database $db) use ($data, $onlineSession): Event {
            $this->guardRoomBooking($db, $data, null);
            $event = $this->create($data);

            if ($event->requiresOnlineSession()) {
                $this->upsertOnlineSession((int) $event->get('id'), $onlineSession ?? []);
            }

            return $event;
        });
    }

    public function update(int|string $id, array $data): ?Event
    {
        $event = $this->find($id);

        if ($event === null) {
            return null;
        }

        if (isset($data['required_resources']) && is_array($data['required_resources'])) {
            $data['required_resources'] = json_encode($data['required_resources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $event->fill($data);
        $event->save();

        return $event;
    }

    public function updateWithOnlineSession(int|string $id, array $data, ?array $onlineSession = null): ?Event
    {
        return $this->db()->transaction(function (Database $db) use ($id, $data, $onlineSession): ?Event {
            $lockedEvent = $db->fetch('SELECT * FROM events WHERE id = :id LIMIT 1 FOR UPDATE', ['id' => $id]);
            if ($lockedEvent === null) {
                return null;
            }

            $this->guardRoomBooking($db, $data + $lockedEvent, (int) $id);
            $updated = $this->update($id, $data);

            if ($updated !== null && $updated->requiresOnlineSession()) {
                $this->upsertOnlineSession((int) $updated->get('id'), $onlineSession ?? []);
            } elseif ($updated !== null) {
                $this->deleteOnlineSession((int) $updated->get('id'));
            }

            return $updated;
        });
    }

    public function delete(int|string $id): bool
    {
        $event = $this->find($id);

        return $event?->delete() ?? false;
    }

    public function upcoming(int $limit = 10): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM events WHERE starts_at >= NOW() ORDER BY starts_at ASC LIMIT ' . max(1, $limit)
        );

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function scheduleLinks(int $eventId): array
    {
        return $this->db()->fetchAll(
            'SELECT * FROM schedule_events WHERE event_id = :event_id ORDER BY id ASC',
            ['event_id' => $eventId]
        );
    }

    public function overlapsForRoom(int $roomId, string $startsAt, string $endsAt, ?int $ignoreEventId = null): array
    {
        $sql = 'SELECT * FROM events
                WHERE room_id = :room_id
                  AND starts_at < :ends_at
                  AND ends_at > :starts_at
                  AND status <> "cancelled"';
        $params = [
            'room_id' => $roomId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ];

        if ($ignoreEventId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreEventId;
        }

        $sql .= ' ORDER BY starts_at ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function forSchedule(int $scheduleId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT events.*
             FROM schedule_events
             INNER JOIN events ON events.id = schedule_events.event_id
             WHERE schedule_events.schedule_id = :schedule_id
             ORDER BY events.starts_at ASC',
            ['schedule_id' => $scheduleId]
        );

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function unassignedForAllocation(): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM events
             WHERE room_id IS NULL
               AND status IN ("draft", "requested")
             ORDER BY starts_at ASC, students_count DESC'
        );

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function upsertOnlineSession(int $eventId, array $data): OnlineSession
    {
        $payload = [
            'event_id' => $eventId,
            'platform' => $data['platform'] ?? 'Zoom',
            'url' => $data['url'] ?? '',
            'access_key' => $data['access_key'] ?? null,
            'access_password' => $data['access_password'] ?? null,
            'host_user_id' => $data['host_user_id'] ?? null,
            'moderator_name' => $data['moderator_name'] ?? null,
            'access_instructions' => $data['access_instructions'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ];

        $existing = $this->db()->fetch(
            'SELECT * FROM online_sessions WHERE event_id = :event_id LIMIT 1',
            ['event_id' => $eventId]
        );

        if ($existing === null) {
            $session = new OnlineSession($payload);
            $session->save();

            return $session;
        }

        $session = new OnlineSession($existing);
        $session->fill($payload);
        $session->save();

        return $session;
    }

    public function findOnlineSession(int $eventId): ?OnlineSession
    {
        $row = $this->db()->fetch(
            'SELECT * FROM online_sessions WHERE event_id = :event_id LIMIT 1',
            ['event_id' => $eventId]
        );

        return $row === null ? null : new OnlineSession($row);
    }

    public function deleteOnlineSession(int $eventId): int
    {
        return $this->db()->execute(
            'DELETE FROM online_sessions WHERE event_id = :event_id',
            ['event_id' => $eventId]
        );
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
