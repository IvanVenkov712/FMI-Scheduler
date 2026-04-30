<?php

declare(strict_types=1);

class OnlineSessionRepository extends GenericModelRepository
{

    public function find(int|string $id): ?OnlineSession
    {
        return $this->findModel(OnlineSession::class, $id);
    }

    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        return array_map(
            static fn (object $session): OnlineSession => $session,
            $this->allModels(OnlineSession::class, $orderBy, $direction)
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
}


