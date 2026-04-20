<?php

declare(strict_types=1);

class RoomController extends CrudController
{
    protected string $table = 'rooms';
    protected string $viewPath = 'rooms';
    protected string $entityName = 'room';
    protected array $fillable = [
        'floor_id', 'room_type_id', 'code', 'name', 'description', 'capacity',
        'covid_capacity', 'x', 'y', 'width', 'height', 'online_url', 'access_key', 'status',
    ];
    protected array $required = ['room_type_id', 'code', 'name'];
    protected array $integerFields = ['floor_id', 'room_type_id', 'capacity', 'covid_capacity', 'x', 'y', 'width', 'height'];
    protected array $listFilters = ['floor_id', 'room_type_id', 'status'];
    protected string $defaultOrder = 'code ASC';

    public function availability(int|string $id): mixed
    {
        $from = (string) $this->input('from', date('Y-m-d 00:00:00'));
        $to = (string) $this->input('to', date('Y-m-d 23:59:59', strtotime('+7 days')));

        return $this->respond('rooms/availability', [
            'room' => $this->findOrFail($id),
            'from' => $from,
            'to' => $to,
            'events' => $this->database()->fetchAll(
                'SELECT * FROM events
                 WHERE room_id = :room_id AND starts_at < :to_date AND ends_at > :from_date
                 ORDER BY starts_at ASC',
                ['room_id' => $id, 'from_date' => $from, 'to_date' => $to]
            ),
            'unavailability' => $this->database()->fetchAll(
                'SELECT * FROM room_unavailability
                 WHERE room_id = :room_id AND starts_at < :to_date AND ends_at > :from_date
                 ORDER BY starts_at ASC',
                ['room_id' => $id, 'from_date' => $from, 'to_date' => $to]
            ),
        ]);
    }

    public function searchAvailable(): mixed
    {
        $capacity = (int) $this->input('capacity', 0);
        $startsAt = (string) $this->input('starts_at', '');
        $endsAt = (string) $this->input('ends_at', '');
        $resources = $this->resourceFilter();

        $params = ['capacity' => $capacity, 'starts_at' => $startsAt, 'ends_at' => $endsAt];
        $resourceJoin = '';
        $resourceHaving = '';

        if ($resources !== []) {
            $placeholders = [];
            foreach ($resources as $index => $resourceId) {
                $key = 'resource_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $resourceId;
            }

            $resourceJoin = ' INNER JOIN room_resources rr ON rr.room_id = rooms.id
                               AND rr.status = "available"
                               AND rr.resource_id IN (' . implode(', ', $placeholders) . ')';
            $resourceHaving = ' HAVING COUNT(DISTINCT rr.resource_id) >= :resource_count';
            $params['resource_count'] = count($resources);
        }

        return $this->respond('rooms/index', [
            'rooms' => $this->database()->fetchAll(
                'SELECT rooms.*
                 FROM rooms' . $resourceJoin . '
                 WHERE rooms.status = "available"
                   AND rooms.capacity >= :capacity
                   AND NOT EXISTS (
                       SELECT 1 FROM events
                       WHERE events.room_id = rooms.id
                         AND events.starts_at < :ends_at
                         AND events.ends_at > :starts_at
                         AND events.status <> "cancelled"
                   )
                 GROUP BY rooms.id' . $resourceHaving . '
                 ORDER BY rooms.capacity ASC, rooms.code ASC',
                $params
            ),
        ]);
    }

    protected function formData(): array
    {
        return [
            'floors' => $this->database()->fetchAll('SELECT id, building_id, number, name FROM floors ORDER BY building_id ASC, number ASC'),
            'room_types' => $this->database()->fetchAll('SELECT * FROM room_types ORDER BY name ASC'),
            'resources' => $this->database()->fetchAll('SELECT * FROM resources ORDER BY name ASC'),
        ];
    }

    private function resourceFilter(): array
    {
        $resources = $this->input('resources', []);

        if (is_string($resources)) {
            $resources = explode(',', $resources);
        }

        return array_values(array_filter(array_map('intval', (array) $resources)));
    }
}
