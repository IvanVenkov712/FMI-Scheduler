<?php

declare(strict_types=1);

class Room extends Model
{
    protected string $table = 'rooms';
    protected array $fillable = [
        'floor_id',
        'room_type_id',
        'code',
        'name',
        'description',
        'capacity',
        'covid_capacity',
        'x',
        'y',
        'width',
        'height',
        'online_url',
        'access_key',
        'status',
    ];

    public function floor(): ?Floor
    {
        $floorId = $this->get('floor_id');

        return $floorId === null ? null : Floor::find((int) $floorId);
    }

    public function roomType(): ?RoomType
    {
        $typeId = $this->get('room_type_id');

        return $typeId === null ? null : RoomType::find((int) $typeId);
    }

    public function resources(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT room_resources.*, resources.name, resources.slug, resources.unit, resources.is_consumable
             FROM room_resources
             INNER JOIN resources ON resources.id = room_resources.resource_id
             WHERE room_resources.room_id = :room_id
             ORDER BY resources.name ASC',
            ['room_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): RoomResource => new RoomResource($row), $rows);
    }

    public function events(string $from = '', string $to = ''): array
    {
        if (!$this->exists()) {
            return [];
        }

        $sql = 'SELECT * FROM events WHERE room_id = :room_id';
        $params = ['room_id' => $this->primaryValue()];

        if ($from !== '' && $to !== '') {
            $sql .= ' AND starts_at < :to_date AND ends_at > :from_date';
            $params['from_date'] = $from;
            $params['to_date'] = $to;
        }

        $sql .= ' ORDER BY starts_at ASC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): Event => new Event($row), $rows);
    }

    public function unavailability(string $from = '', string $to = ''): array
    {
        if (!$this->exists()) {
            return [];
        }

        $sql = 'SELECT * FROM room_unavailability WHERE room_id = :room_id';
        $params = ['room_id' => $this->primaryValue()];

        if ($from !== '' && $to !== '') {
            $sql .= ' AND starts_at < :to_date AND ends_at > :from_date';
            $params['from_date'] = $from;
            $params['to_date'] = $to;
        }

        $sql .= ' ORDER BY starts_at ASC';

        return $this->db()->fetchAll($sql, $params);
    }

    public function isAvailableBetween(string $startsAt, string $endsAt): bool
    {
        if (!$this->exists() || !$this->isAvailable()) {
            return false;
        }

        $conflict = $this->db()->fetch(
            'SELECT id
             FROM events
             WHERE room_id = :room_id
               AND starts_at < :ends_at
               AND ends_at > :starts_at
               AND status <> "cancelled"
             LIMIT 1',
            [
                'room_id' => $this->primaryValue(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        );

        if ($conflict !== null) {
            return false;
        }

        $blocked = $this->db()->fetch(
            'SELECT id
             FROM room_unavailability
             WHERE room_id = :room_id
               AND starts_at < :ends_at
               AND ends_at > :starts_at
             LIMIT 1',
            [
                'room_id' => $this->primaryValue(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        );

        return $blocked === null;
    }

    public function effectiveCapacity(bool $preferCovid = true): int
    {
        if ($preferCovid && $this->get('covid_capacity') !== null) {
            return (int) $this->get('covid_capacity');
        }

        return (int) $this->get('capacity', 0);
    }

    public function isAvailable(): bool
    {
        return $this->get('status', 'available') === 'available';
    }

    public function supportsOnline(): bool
    {
        $roomType = $this->roomType();

        if ($roomType !== null) {
            return $roomType->supportsOnline();
        }

        return (string) $this->get('online_url', '') !== '';
    }
}
