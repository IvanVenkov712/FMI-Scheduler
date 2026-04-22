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
        return Event::find($id);
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

    public function delete(int|string $id): bool
    {
        $event = $this->find($id);

        return $event?->delete() ?? false;
    }

    public function upcoming(int $limit = 10): array
    {
        return Event::upcoming($limit);
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

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
