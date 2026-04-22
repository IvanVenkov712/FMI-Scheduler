<?php

declare(strict_types=1);

class OnlineSessionRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?OnlineSession
    {
        return (new GenericModelRepository($this->database))->find(OnlineSession::class, $id);
    }

    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        return array_map(
            static fn (object $session): OnlineSession => $session,
            (new GenericModelRepository($this->database))->all(OnlineSession::class, $orderBy, $direction)
        );
    }

    public function findByEventId(int $eventId): ?OnlineSession
    {
        $row = $this->db()->fetch(
            'SELECT * FROM online_sessions WHERE event_id = :event_id LIMIT 1',
            ['event_id' => $eventId]
        );

        return $row === null ? null : new OnlineSession($row);
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
