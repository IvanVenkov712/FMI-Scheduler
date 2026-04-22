<?php

declare(strict_types=1);

class ScheduleService
{
    public function __construct(
        private readonly ScheduleRepository $schedules = new ScheduleRepository(),
        private readonly EventRepository $events = new EventRepository(),
        private readonly EventService $eventService = new EventService(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function all(array $filters = []): array
    {
        return $this->schedules->all($filters);
    }

    public function find(int|string $id): ?Schedule
    {
        return $this->schedules->find($id);
    }

    public function create(array $data, ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'draft';
        $data['type'] = $data['type'] ?? 'semester';
        $data['semester'] = $data['semester'] ?? 'none';
        $data['created_by'] = $data['created_by'] ?? $userId;

        $dateErrors = $this->validation->validateDateRange(
            (string) ($data['starts_on'] ?? ''),
            (string) ($data['ends_on'] ?? ''),
            'starts_on',
            'ends_on'
        );

        $result = $this->validation->validate($data, [
            'name' => 'required|string|max:190',
            'type' => 'required|in:semester,exam_session,ad_hoc',
            'academic_year' => 'nullable|string|max:20',
            'semester' => 'required|in:winter,summer,none',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date',
            'status' => 'required|in:draft,published,archived',
        ]);

        if (!$result['valid'] || $dateErrors !== []) {
            return ['success' => false, 'errors' => array_merge_recursive($result['errors'], $dateErrors)];
        }

        $schedule = $this->schedules->create($data);
        $this->auditLogs->record('create', 'schedule', $schedule->get('id'), null, $schedule->toArray(), $userId);

        return ['success' => true, 'schedule' => $schedule];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $schedule = $this->schedules->find($id);
        if ($schedule === null) {
            return ['success' => false, 'errors' => ['schedule' => ['Schedule not found.']]];
        }

        $merged = $data + $schedule->toArray();
        $dateErrors = $this->validation->validateDateRange(
            (string) ($merged['starts_on'] ?? ''),
            (string) ($merged['ends_on'] ?? ''),
            'starts_on',
            'ends_on'
        );

        $result = $this->validation->validate($merged, [
            'name' => 'required|string|max:190',
            'type' => 'required|in:semester,exam_session,ad_hoc',
            'academic_year' => 'nullable|string|max:20',
            'semester' => 'required|in:winter,summer,none',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date',
            'status' => 'required|in:draft,published,archived',
        ]);

        if (!$result['valid'] || $dateErrors !== []) {
            return ['success' => false, 'errors' => array_merge_recursive($result['errors'], $dateErrors)];
        }

        $old = $schedule->toArray();
        $updated = $this->schedules->update($id, $data);
        $this->auditLogs->record('update', 'schedule', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'schedule' => $updated];
    }

    public function attachEvent(int $scheduleId, int $eventId, ?int $roomId = null, string $allocationStatus = 'manual', ?string $notes = null, ?int $userId = null): array
    {
        $schedule = $this->schedules->find($scheduleId);
        if ($schedule === null) {
            return ['success' => false, 'errors' => ['schedule' => ['Schedule not found.']]];
        }

        $event = $this->events->find($eventId);
        if ($event === null) {
            return ['success' => false, 'errors' => ['event' => ['Event not found.']]];
        }

        $errors = $this->validateAttachEvent($schedule, $event, $roomId, $allocationStatus);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $effectiveRoomId = $roomId ?? ($event->get('room_id') !== null ? (int) $event->get('room_id') : null);

        if ($effectiveRoomId !== null && (int) $event->get('room_id', 0) !== $effectiveRoomId) {
            $assignResult = $this->eventService->assignRoom((int) $event->get('id'), $effectiveRoomId, $userId);
            if (!($assignResult['success'] ?? false)) {
                return ['success' => false, 'errors' => (array) ($assignResult['errors'] ?? ['room' => ['Room assignment failed.']])];
            }

            $event = $this->events->find($eventId) ?? $event;
        }

        $normalizedStatus = $this->normalizeAllocationStatus($allocationStatus, $effectiveRoomId);
        $this->schedules->attachEvent($scheduleId, $eventId, $effectiveRoomId, $normalizedStatus, $notes);
        $this->auditLogs->record('attach', 'schedule_event', $eventId, null, [
            'schedule_id' => $scheduleId,
            'event_id' => $eventId,
            'assigned_room_id' => $effectiveRoomId,
            'allocation_status' => $normalizedStatus,
            'conflict_notes' => $notes,
        ], $userId);

        return ['success' => true];
    }

    public function replaceEvents(int $scheduleId, array $items): void
    {
        $this->schedules->clearEvents($scheduleId);

        foreach ($items as $item) {
            if (!is_array($item) || (int) ($item['event_id'] ?? 0) <= 0) {
                continue;
            }

            $attachResult = $this->attachEvent(
                $scheduleId,
                (int) $item['event_id'],
                isset($item['assigned_room_id']) && $item['assigned_room_id'] !== '' ? (int) $item['assigned_room_id'] : null,
                (string) ($item['allocation_status'] ?? 'manual'),
                isset($item['conflict_notes']) && $item['conflict_notes'] !== '' ? (string) $item['conflict_notes'] : null
            );

            if (!($attachResult['success'] ?? false)) {
                throw new RuntimeException($this->flattenErrors((array) ($attachResult['errors'] ?? [])));
            }
        }
    }

    public function detachEvent(int $scheduleId, int $eventId): int
    {
        return $this->schedules->detachEvent($scheduleId, $eventId);
    }

    public function calendar(?int $scheduleId = null): array
    {
        return $this->schedules->calendar($scheduleId);
    }

    public function eventsNeedingAllocation(int $scheduleId): array
    {
        return $this->schedules->eventsNeedingAllocation($scheduleId);
    }

    public function delete(int|string $id, ?int $userId = null): bool
    {
        $schedule = $this->schedules->find($id);
        if ($schedule === null) {
            return false;
        }

        $deleted = $this->schedules->delete($id);
        if ($deleted) {
            $this->auditLogs->record('delete', 'schedule', $id, $schedule->toArray(), null, $userId);
        }

        return $deleted;
    }

    private function validateAttachEvent(Schedule $schedule, Event $event, ?int $roomId, string $allocationStatus): array
    {
        $errors = [];
        $startsAt = (string) $event->get('starts_at', '');
        $endsAt = (string) $event->get('ends_at', '');

        if (!$this->eventFitsSchedule($schedule, $startsAt, $endsAt)) {
            $errors['event'][] = 'Event falls outside the schedule period.';
        }

        if (!in_array($allocationStatus, ['manual', 'auto_assigned', 'conflict', 'unassigned'], true)) {
            $errors['allocation_status'][] = 'Invalid allocation status.';
        }

        $effectiveRoomId = $roomId ?? ($event->get('room_id') !== null ? (int) $event->get('room_id') : null);
        $deliveryMode = (string) $event->get('delivery_mode', 'physical');

        if ($effectiveRoomId === null && in_array($allocationStatus, ['manual', 'auto_assigned'], true) && $deliveryMode !== 'online') {
            $errors['assigned_room_id'][] = 'Manual or auto-assigned schedule entries require a room for non-online events.';
        }

        return $errors;
    }

    private function eventFitsSchedule(Schedule $schedule, string $startsAt, string $endsAt): bool
    {
        $startTs = strtotime($startsAt);
        $endTs = strtotime($endsAt);
        $scheduleStartTs = strtotime((string) $schedule->get('starts_on', '') . ' 00:00:00');
        $scheduleEndTs = strtotime((string) $schedule->get('ends_on', '') . ' 23:59:59');

        if ($startTs === false || $endTs === false || $scheduleStartTs === false || $scheduleEndTs === false) {
            return false;
        }

        return $startTs >= $scheduleStartTs && $endTs <= $scheduleEndTs;
    }

    private function normalizeAllocationStatus(string $allocationStatus, ?int $effectiveRoomId): string
    {
        if ($effectiveRoomId === null && in_array($allocationStatus, ['manual', 'auto_assigned'], true)) {
            return 'unassigned';
        }

        return $allocationStatus;
    }

    private function flattenErrors(array $errors): string
    {
        $messages = [];

        foreach ($errors as $fieldErrors) {
            foreach ((array) $fieldErrors as $message) {
                $messages[] = (string) $message;
            }
        }

        return $messages === [] ? 'Schedule event validation failed.' : implode('; ', $messages);
    }
}
