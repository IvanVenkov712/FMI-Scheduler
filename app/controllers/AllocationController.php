<?php

declare(strict_types=1);

class AllocationController extends Controller
{
    public function search(): mixed
    {
        $capacity = (int) $this->input('capacity', 0);
        $startsAt = (string) $this->input('starts_at', '');
        $endsAt = (string) $this->input('ends_at', '');

        $rooms = [];
        if ($startsAt !== '' && $endsAt !== '') {
            $rooms = $this->availableRooms($capacity, $startsAt, $endsAt);
        }

        return $this->respond('allocation/search', [
            'rooms' => $rooms,
            'filters' => compact('capacity', 'startsAt', 'endsAt'),
        ]);
    }

    public function autoAssign(int|string|null $scheduleId = null): mixed
    {
        $data = $this->body() + $this->input();
        $scheduleId ??= $data['schedule_id'] ?? null;
        $runId = $this->createRun($scheduleId, $data);
        $assignments = [];
        $conflicts = [];

        $events = $this->eventsForAllocation($scheduleId);

        foreach ($events as $event) {
            $room = $this->bestRoomForEvent($event);

            if ($room === null) {
                $conflicts[] = [
                    'event_id' => $event['id'],
                    'title' => $event['title'],
                    'reason' => 'No free room matches capacity, resources and time constraints.',
                ];
                $this->markScheduleEvent($scheduleId, $event['id'], null, 'conflict', 'No suitable room found.');
                continue;
            }

            $assignments[] = [
                'event_id' => $event['id'],
                'room_id' => $room['id'],
                'room_code' => $room['code'],
            ];
            $this->markScheduleEvent($scheduleId, $event['id'], $room['id'], 'auto_assigned', null);
        }

        $result = [
            'assignments' => $assignments,
            'conflicts' => $conflicts,
            'summary' => [
                'events' => count($events),
                'assigned' => count($assignments),
                'conflicts' => count($conflicts),
            ],
        ];

        $this->finishRun($runId, $result, $conflicts === [] ? 'completed' : 'failed');

        return $this->respond('allocation/result', [
            'run' => $this->run($runId),
            'result' => $result,
        ]);
    }

    public function result(int|string $id): mixed
    {
        $run = $this->run($id);

        return $this->respond('allocation/result', [
            'run' => $run,
            'result' => json_decode((string) ($run['result_data'] ?? '{}'), true) ?: [],
        ]);
    }

    private function eventsForAllocation(int|string|null $scheduleId): array
    {
        if ($scheduleId !== null && $scheduleId !== '') {
            return $this->database()->fetchAll(
                'SELECT events.*
                 FROM schedule_events
                 INNER JOIN events ON events.id = schedule_events.event_id
                 WHERE schedule_events.schedule_id = :schedule_id
                   AND schedule_events.allocation_status IN ("unassigned", "conflict")
                 ORDER BY events.starts_at ASC, events.students_count DESC',
                ['schedule_id' => $scheduleId]
            );
        }

        return $this->database()->fetchAll(
            'SELECT * FROM events
             WHERE room_id IS NULL AND status IN ("draft", "requested")
             ORDER BY starts_at ASC, students_count DESC'
        );
    }

    private function bestRoomForEvent(array $event): ?array
    {
        $capacity = (int) ($event['students_count'] ?? 0);
        $rooms = $this->availableRooms($capacity, (string) $event['starts_at'], (string) $event['ends_at'], (string) $event['delivery_mode']);

        return $rooms[0] ?? null;
    }

    private function availableRooms(int $capacity, string $startsAt, string $endsAt, string $deliveryMode = 'physical'): array
    {
        $capacityColumn = $this->covidCapacityActive() ? 'COALESCE(rooms.covid_capacity, rooms.capacity)' : 'rooms.capacity';
        $deliverySql = $deliveryMode === 'online' ? 'room_types.supports_online = 1' : 'rooms.status = "available"';

        return $this->database()->fetchAll(
            'SELECT rooms.*, room_types.slug AS room_type
             FROM rooms
             INNER JOIN room_types ON room_types.id = rooms.room_type_id
             WHERE ' . $deliverySql . '
               AND ' . $capacityColumn . ' >= :capacity
               AND NOT EXISTS (
                   SELECT 1 FROM events
                   WHERE events.room_id = rooms.id
                     AND events.starts_at < :ends_at
                     AND events.ends_at > :starts_at
                     AND events.status <> "cancelled"
               )
               AND NOT EXISTS (
                   SELECT 1 FROM room_unavailability
                   WHERE room_unavailability.room_id = rooms.id
                     AND room_unavailability.starts_at < :ends_at
                     AND room_unavailability.ends_at > :starts_at
               )
             ORDER BY ' . $capacityColumn . ' ASC, rooms.code ASC',
            ['capacity' => $capacity, 'starts_at' => $startsAt, 'ends_at' => $endsAt]
        );
    }

    private function covidCapacityActive(): bool
    {
        $constraint = $this->database()->fetch(
            'SELECT id FROM movement_constraints
             WHERE is_active = 1
               AND type IN ("covid", "capacity")
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             LIMIT 1'
        );

        return $constraint !== null;
    }

    private function markScheduleEvent(int|string|null $scheduleId, int|string $eventId, int|string|null $roomId, string $status, ?string $notes): void
    {
        if ($scheduleId === null || $scheduleId === '') {
            if ($roomId !== null) {
                $this->database()->execute(
                    'UPDATE events SET room_id = :room_id, status = "scheduled" WHERE id = :event_id',
                    ['room_id' => $roomId, 'event_id' => $eventId]
                );
            }
            return;
        }

        $this->database()->insert(
            'INSERT INTO schedule_events (schedule_id, event_id, assigned_room_id, allocation_status, conflict_notes)
             VALUES (:schedule_id, :event_id, :room_id, :status, :notes)
             ON DUPLICATE KEY UPDATE assigned_room_id = VALUES(assigned_room_id),
                                     allocation_status = VALUES(allocation_status),
                                     conflict_notes = VALUES(conflict_notes)',
            [
                'schedule_id' => $scheduleId,
                'event_id' => $eventId,
                'room_id' => $roomId,
                'status' => $status,
                'notes' => $notes,
            ]
        );
    }

    private function createRun(int|string|null $scheduleId, array $input): string
    {
        return $this->database()->insert(
            'INSERT INTO allocation_runs (schedule_id, requested_by, input_data, status, started_at)
             VALUES (:schedule_id, :requested_by, :input_data, "running", NOW())',
            [
                'schedule_id' => $scheduleId ?: null,
                'requested_by' => $_SESSION['user']['id'] ?? null,
                'input_data' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
    }

    private function finishRun(int|string $id, array $result, string $status): void
    {
        $this->database()->execute(
            'UPDATE allocation_runs SET result_data = :result_data, status = :status, finished_at = NOW() WHERE id = :id',
            [
                'id' => $id,
                'status' => $status,
                'result_data' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
    }

    private function run(int|string $id): array
    {
        $run = $this->database()->fetch('SELECT * FROM allocation_runs WHERE id = :id', ['id' => $id]);

        if ($run === null) {
            throw new RuntimeException('Allocation run not found.');
        }

        return $run;
    }

    private function respond(string $view, array $data): mixed
    {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || $this->input('format') === 'json') {
            return $data;
        }

        return $this->render($view, $data);
    }
}
