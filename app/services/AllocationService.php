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
                'blocked' => $score['blocked'],
                'blocked_reason' => $score['blocked_reason'],
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
            'event_id' => $eventId,
            'schedule_id' => $scheduleId,
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

            if (!empty($selected['blocked'])) {
                $alternatives[] = [
                    'room_id' => $roomId,
                    'room_code' => (string) $room->get('code'),
                    'score' => (int) ($selected['score'] ?? 0),
                    'explanation' => (string) ($selected['explanation'] ?? ''),
                    'reason' => (string) ($selected['blocked_reason'] ?? 'Movement constraints reject this room.'),
                ];
                continue;
            }

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
        $blocked = false;
        $blockedReason = null;
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

        $movement = $this->movementAssessment($room, $criteria);
        $score += (int) ($movement['score_delta'] ?? 0);

        foreach ((array) ($movement['notes'] ?? []) as $note) {
            $explanations[] = (string) $note;
        }

        if (!empty($movement['blocked'])) {
            $blocked = true;
            $blockedReason = (string) ($movement['blocked_reason'] ?? 'Movement constraints reject this room.');
            $score = -100000;
            $explanations[] = $blockedReason;
        }

        return [
            'score' => $score,
            'explanation' => implode('; ', $explanations),
            'blocked' => $blocked,
            'blocked_reason' => $blockedReason,
        ];
    }

    private function movementAssessment(Room $candidateRoom, array $criteria): array
    {
        $eventId = isset($criteria['event_id']) ? (int) $criteria['event_id'] : 0;
        $scheduleId = isset($criteria['schedule_id']) ? (int) $criteria['schedule_id'] : 0;
        $studentsCount = (int) ($criteria['students_count'] ?? $criteria['capacity'] ?? 0);
        $movementType = (string) ($criteria['movement_type'] ?? 'walking');
        $notes = [];
        $scoreDelta = 0;

        foreach ($this->adjacentScheduledEvents($scheduleId, $eventId) as $adjacent) {
            $otherRoomId = (int) ($adjacent['room_id'] ?? 0);
            if ($otherRoomId <= 0 || $otherRoomId === (int) $candidateRoom->get('id')) {
                continue;
            }

            $fromRoomId = $adjacent['direction'] === 'before' ? $otherRoomId : (int) $candidateRoom->get('id');
            $toRoomId = $adjacent['direction'] === 'before' ? (int) $candidateRoom->get('id') : $otherRoomId;
            $routeDecision = $this->routes->recommended($fromRoomId, $toRoomId, true, $movementType);

            if ($routeDecision === null) {
                return [
                    'blocked' => true,
                    'blocked_reason' => sprintf(
                        'No allowed %s route between %s and %s.',
                        $movementType,
                        $adjacent['room_code'] ?? ('room #' . $otherRoomId),
                        (string) $candidateRoom->get('code')
                    ),
                    'notes' => $notes,
                    'score_delta' => $scoreDelta,
                ];
            }

            /** @var Route $route */
            $route = $routeDecision['route'];
            if (!$this->constraints->routeAllowed($route)) {
                return [
                    'blocked' => true,
                    'blocked_reason' => sprintf('Route "%s" is restricted by movement constraints.', (string) $route->get('name')),
                    'notes' => $notes,
                    'score_delta' => $scoreDelta,
                ];
            }

            $travelMinutes = (int) ($route->durationFor($movementType) ?? $route->durationFor() ?? 0);
            $otherRoom = Room::find($otherRoomId);
            $bufferMinutes = max(
                $this->constraints->recommendedBufferMinutes($candidateRoom),
                $otherRoom instanceof Room ? $this->constraints->recommendedBufferMinutes($otherRoom) : 0
            );
            $availableGap = (int) ($adjacent['gap_minutes'] ?? 0);
            $requiredGap = $travelMinutes + $bufferMinutes;

            $notes[] = sprintf(
                '%s route %s requires %d min travel + %d min buffer (gap %d min)',
                $adjacent['direction'] === 'before' ? 'incoming' : 'outgoing',
                (string) $route->get('name'),
                $travelMinutes,
                $bufferMinutes,
                $availableGap
            );

            if ($availableGap < $requiredGap) {
                return [
                    'blocked' => true,
                    'blocked_reason' => sprintf(
                        'Movement conflict: %s gap of %d min is below required %d min for route "%s".',
                        $adjacent['direction'] === 'before' ? 'incoming' : 'outgoing',
                        $availableGap,
                        $requiredGap,
                        (string) $route->get('name')
                    ),
                    'notes' => $notes,
                    'score_delta' => $scoreDelta,
                ];
            }

            $scoreDelta -= $travelMinutes * 3;
            if ($availableGap < $requiredGap + 10) {
                $scoreDelta -= 40;
                $notes[] = 'tight movement window penalty applied';
            }

            $flowLimit = $this->movementFlowLimit($candidateRoom, $route);
            $movingPeople = $studentsCount + (int) ($adjacent['students_count'] ?? 0);

            if ($flowLimit !== null) {
                $notes[] = sprintf('movement flow %d/%d people', $movingPeople, $flowLimit);

                if ($movingPeople > $flowLimit) {
                    return [
                        'blocked' => true,
                        'blocked_reason' => sprintf(
                            'Movement conflict: %d moving people exceed flow limit %d.',
                            $movingPeople,
                            $flowLimit
                        ),
                        'notes' => $notes,
                        'score_delta' => $scoreDelta,
                    ];
                }

                if ($movingPeople > (int) floor($flowLimit * 0.8)) {
                    $scoreDelta -= 60;
                    $notes[] = 'high movement load penalty applied';
                }
            }
        }

        return [
            'blocked' => false,
            'blocked_reason' => null,
            'notes' => $notes,
            'score_delta' => $scoreDelta,
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
        $eventResources = $event->requiredResources();
        $resourceIds = $this->normalizeRequiredResourceIds($eventResources);

        if ($resourceIds !== []) {
            return $resourceIds;
        }

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

    private function normalizeRequiredResourceIds(array $requiredResources): array
    {
        $resourceIds = [];

        foreach ($requiredResources as $resource) {
            if (is_array($resource)) {
                $resourceId = $resource['resource_id'] ?? $resource['id'] ?? null;
            } else {
                $resourceId = $resource;
            }

            if ((int) $resourceId > 0) {
                $resourceIds[] = (int) $resourceId;
            }
        }

        return array_values(array_unique($resourceIds));
    }

    private function adjacentScheduledEvents(int $scheduleId, int $eventId): array
    {
        if ($scheduleId <= 0 || $eventId <= 0) {
            return [];
        }

        $calendar = $this->schedules->calendar($scheduleId);
        $currentIndex = null;

        foreach ($calendar as $index => $entry) {
            if ((int) ($entry['id'] ?? 0) === $eventId) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            return [];
        }

        $current = $calendar[$currentIndex];
        $adjacent = [];

        for ($i = $currentIndex - 1; $i >= 0; $i--) {
            $entry = $calendar[$i];
            $roomId = (int) ($entry['assigned_room_id'] ?? $entry['room_id'] ?? 0);

            if ($roomId <= 0) {
                continue;
            }

            $gap = $this->minutesBetween((string) ($entry['ends_at'] ?? ''), (string) ($current['starts_at'] ?? ''));
            if ($gap === null) {
                continue;
            }

            $adjacent[] = [
                'direction' => 'before',
                'event_id' => (int) ($entry['id'] ?? 0),
                'room_id' => $roomId,
                'room_code' => $entry['room_code'] ?? null,
                'students_count' => (int) ($entry['students_count'] ?? 0),
                'gap_minutes' => $gap,
            ];
            break;
        }

        for ($i = $currentIndex + 1, $count = count($calendar); $i < $count; $i++) {
            $entry = $calendar[$i];
            $roomId = (int) ($entry['assigned_room_id'] ?? $entry['room_id'] ?? 0);

            if ($roomId <= 0) {
                continue;
            }

            $gap = $this->minutesBetween((string) ($current['ends_at'] ?? ''), (string) ($entry['starts_at'] ?? ''));
            if ($gap === null) {
                continue;
            }

            $adjacent[] = [
                'direction' => 'after',
                'event_id' => (int) ($entry['id'] ?? 0),
                'room_id' => $roomId,
                'room_code' => $entry['room_code'] ?? null,
                'students_count' => (int) ($entry['students_count'] ?? 0),
                'gap_minutes' => $gap,
            ];
            break;
        }

        return $adjacent;
    }

    private function movementFlowLimit(Room $room, Route $route): ?int
    {
        $limits = [];
        $buildingId = (int) ($room->floor()?->get('building_id') ?? 0);
        $floorId = (int) ($room->get('floor_id') ?? 0);
        $roomId = (int) ($room->get('id') ?? 0);
        $routeId = (int) ($route->get('id') ?? 0);

        foreach ($this->constraints->active() as $constraint) {
            $ruleData = $constraint->ruleData();
            $limit = $this->extractMovementLimit($ruleData);

            if ($limit === null) {
                continue;
            }

            $scope = (string) $constraint->get('scope', 'global');
            $matches = match ($scope) {
                'global' => true,
                'building' => (int) $constraint->get('building_id') === $buildingId,
                'floor' => (int) $constraint->get('floor_id') === $floorId,
                'room' => (int) $constraint->get('room_id') === $roomId,
                'route' => (int) $constraint->get('route_id') === $routeId,
                default => false,
            };

            if ($matches) {
                $limits[] = $limit;
            }
        }

        return $limits === [] ? null : min($limits);
    }

    private function extractMovementLimit(array $ruleData): ?int
    {
        foreach (['max_people_in_motion', 'max_simultaneous_people', 'max_flow', 'max_group_size'] as $key) {
            if (isset($ruleData[$key]) && (int) $ruleData[$key] > 0) {
                return (int) $ruleData[$key];
            }
        }

        return null;
    }

    private function minutesBetween(string $from, string $to): ?int
    {
        $fromTs = strtotime($from);
        $toTs = strtotime($to);

        if ($fromTs === false || $toTs === false) {
            return null;
        }

        return (int) floor(($toTs - $fromTs) / 60);
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
