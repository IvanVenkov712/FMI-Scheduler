<?php

declare(strict_types=1);

class AllocationService
{
    public function __construct(
        private readonly RoomService $rooms = new RoomService(),
        private readonly EventService $eventService = new EventService(),
        private readonly EventRepository $events = new EventRepository(),
        private readonly ScheduleRepository $schedules = new ScheduleRepository(),
        private readonly RouteService $routes = new RouteService(),
        private readonly CovidConstraintService $constraints = new CovidConstraintService(),
        private readonly ?Database $database = null,
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository()
    ) {
    }

    public function searchAvailableRooms(array $criteria): array
    {
        $capacity = (int) ($criteria['students_count'] ?? $criteria['capacity'] ?? 0);
        $startsAt = (string) ($criteria['starts_at'] ?? '');
        $endsAt = (string) ($criteria['ends_at'] ?? '');
        $deliveryMode = (string) ($criteria['delivery_mode'] ?? 'physical');
        $requiredResourceIds = array_values(array_filter(array_map('intval', (array) ($criteria['resource_ids'] ?? []))));

        $candidates = $this->rooms->availableBetween(
            $capacity,
            $startsAt,
            $endsAt,
            $deliveryMode,
            $requiredResourceIds,
            true
        );

        $scored = [];

        foreach ($candidates as $room) {
            $score = $this->scoreRoom($room, $capacity, $requiredResourceIds, $criteria);
            $scored[] = [
                'room' => $room,
                'score' => $score['score'],
                'explanation' => $score['explanation'],
            ];
        }

        usort(
            $scored,
            static fn (array $left, array $right): int => $right['score'] <=> $left['score']
        );

        return $scored;
    }

    public function allocateSchedule(?int $scheduleId = null, array $input = [], ?int $userId = null): array
    {
        $runId = $this->createRun($scheduleId, $input, $userId);
        $events = $scheduleId === null
            ? $this->events->unassignedForAllocation()
            : $this->schedules->eventsNeedingAllocation($scheduleId);

        $assignments = [];
        $conflicts = [];

        foreach ($events as $event) {
            $decision = $this->allocateEvent((int) $event->get('id'), $scheduleId, $userId);

            if ($decision['success']) {
                $assignments[] = $decision;
                continue;
            }

            $conflicts[] = [
                'event_id' => (int) $event->get('id'),
                'title' => (string) $event->get('title'),
                'reason' => $decision['reason'] ?? 'No suitable room found.',
                'alternatives' => $decision['alternatives'] ?? [],
            ];
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

        return ['run_id' => $runId, 'result' => $result];
    }

    public function allocateEvent(int $eventId, ?int $scheduleId = null, ?int $userId = null): array
    {
        $event = $this->events->find($eventId);
        if ($event === null) {
            return ['success' => false, 'reason' => 'Event not found.'];
        }

        $resourceIds = $this->extractRequiredResourceIds($event);
        $rankedRooms = $this->searchAvailableRooms([
            'students_count' => (int) $event->get('students_count', 0),
            'starts_at' => (string) $event->get('starts_at'),
            'ends_at' => (string) $event->get('ends_at'),
            'delivery_mode' => (string) $event->get('delivery_mode', 'physical'),
            'resource_ids' => $resourceIds,
            'preferred_room_id' => $event->get('room_id'),
        ]);

        if ($rankedRooms === []) {
            $this->markAllocation($scheduleId, $eventId, null, 'conflict', 'No suitable room found.');

            return [
                'success' => false,
                'reason' => 'No suitable room matches the time, capacity and resource requirements.',
                'alternatives' => [],
            ];
        }

        $alternatives = [];

        foreach ($rankedRooms as $selected) {
            /** @var Room $room */
            $room = $selected['room'];
            $roomId = (int) $room->get('id');
            $assignResult = $this->eventService->assignRoom($eventId, $roomId, $userId);

            if (!($assignResult['success'] ?? false)) {
                $alternatives[] = [
                    'room_id' => $roomId,
                    'room_code' => (string) $room->get('code'),
                    'score' => (int) ($selected['score'] ?? 0),
                    'explanation' => (string) ($selected['explanation'] ?? ''),
                    'reason' => $this->flattenErrors((array) ($assignResult['errors'] ?? [])),
                ];
                continue;
            }

            $this->markAllocation($scheduleId, $eventId, $roomId, 'auto_assigned', null);

            $payload = [
                'event_id' => $eventId,
                'room_id' => $roomId,
                'room_code' => (string) $room->get('code'),
                'score' => (int) ($selected['score'] ?? 0),
                'explanation' => (string) ($selected['explanation'] ?? ''),
            ];

            $this->auditLogs->record('allocate', 'event', $eventId, null, $payload, $userId);

            return ['success' => true] + $payload;
        }

        $reason = 'No suitable room remained available during allocation.';
        $this->markAllocation($scheduleId, $eventId, null, 'conflict', $reason);

        return [
            'success' => false,
            'reason' => $reason,
            'alternatives' => $alternatives,
        ];
    }

    private function scoreRoom(Room $room, int $capacity, array $requiredResourceIds, array $criteria): array
    {
        $score = 1000;
        $explanations = [];
        $effectiveCapacity = $this->constraints->effectiveRoomCapacity($room);

        $score -= max(0, $effectiveCapacity - $capacity);
        $explanations[] = sprintf('capacity %d for %d students', $effectiveCapacity, $capacity);

        if (!empty($criteria['preferred_room_id']) && (int) $criteria['preferred_room_id'] === (int) $room->get('id')) {
            $score += 120;
            $explanations[] = 'preferred room matched';
        }

        $floor = $room->floor();
        if ($floor !== null) {
            $score += max(0, 50 - (int) $floor->get('number', 0) * 5);
            $explanations[] = 'floor proximity bonus applied';
        }

        $roomResourceIds = array_map(
            static fn (RoomResource $resource): int => (int) $resource->get('resource_id'),
            $room->resources()
        );

        $matchedResources = 0;
        foreach ($requiredResourceIds as $resourceId) {
            if (in_array((int) $resourceId, $roomResourceIds, true)) {
                $matchedResources++;
            }
        }

        $score += $matchedResources * 40;
        if ($requiredResourceIds !== []) {
            $explanations[] = sprintf('matched %d/%d required resources', $matchedResources, count($requiredResourceIds));
        }

        $bufferPenalty = $this->bufferPenalty($room, (string) ($criteria['starts_at'] ?? ''), (string) ($criteria['ends_at'] ?? ''));
        $score -= $bufferPenalty;

        if ($bufferPenalty > 0) {
            $explanations[] = sprintf('buffer penalty %d', $bufferPenalty);
        }

        return [
            'score' => $score,
            'explanation' => implode('; ', $explanations),
        ];
    }

    private function bufferPenalty(Room $room, string $startsAt, string $endsAt): int
    {
        $buffer = $this->constraints->recommendedBufferMinutes($room);
        if ($buffer === 0) {
            return 0;
        }

        $events = $room->events();
        $startTs = strtotime($startsAt);
        $endTs = strtotime($endsAt);

        foreach ($events as $event) {
            $eventStart = strtotime((string) $event->get('starts_at'));
            $eventEnd = strtotime((string) $event->get('ends_at'));

            if ($startTs === false || $endTs === false || $eventStart === false || $eventEnd === false) {
                continue;
            }

            $beforeGap = abs($startTs - $eventEnd) / 60;
            $afterGap = abs($eventStart - $endTs) / 60;

            if ($beforeGap < $buffer || $afterGap < $buffer) {
                return 60;
            }
        }

        return 0;
    }

    private function extractRequiredResourceIds(Event $event): array
    {
        $sessionLinks = $event->scheduleLinks();
        $resourceIds = [];

        foreach ($sessionLinks as $link) {
            if (!empty($link['conflict_notes'])) {
                $decoded = json_decode((string) $link['conflict_notes'], true);
                if (is_array($decoded) && isset($decoded['resource_ids']) && is_array($decoded['resource_ids'])) {
                    $resourceIds = array_merge($resourceIds, array_map('intval', $decoded['resource_ids']));
                }
            }
        }

        return array_values(array_unique(array_filter($resourceIds)));
    }

    private function markAllocation(?int $scheduleId, int $eventId, ?int $roomId, string $status, ?string $notes): void
    {
        if ($scheduleId === null) {
            return;
        }

        $this->schedules->attachEvent($scheduleId, $eventId, $roomId, $status, $notes);
    }

    private function createRun(?int $scheduleId, array $input, ?int $userId): string
    {
        return $this->db()->insert(
            'INSERT INTO allocation_runs (schedule_id, requested_by, input_data, status, started_at)
             VALUES (:schedule_id, :requested_by, :input_data, :status, :started_at)',
            [
                'schedule_id' => $scheduleId,
                'requested_by' => $userId,
                'input_data' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private function finishRun(string $runId, array $result, string $status): void
    {
        $this->db()->execute(
            'UPDATE allocation_runs
             SET result_data = :result_data, status = :status, finished_at = :finished_at
             WHERE id = :id',
            [
                'id' => $runId,
                'result_data' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => $status,
                'finished_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private function flattenErrors(array $errors): string
    {
        $messages = [];

        foreach ($errors as $fieldErrors) {
            foreach ((array) $fieldErrors as $message) {
                $messages[] = (string) $message;
            }
        }

        return $messages === [] ? 'Allocation failed.' : implode('; ', $messages);
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
