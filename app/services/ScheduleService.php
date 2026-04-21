<?php

declare(strict_types=1);

class ScheduleService
{
    public function __construct(
        private readonly ScheduleRepository $schedules = new ScheduleRepository(),
        private readonly EventRepository $events = new EventRepository(),
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

    public function attachEvent(int $scheduleId, int $eventId, ?int $roomId = null, string $allocationStatus = 'manual', ?string $notes = null): void
    {
        $this->schedules->attachEvent($scheduleId, $eventId, $roomId, $allocationStatus, $notes);
    }

    public function replaceEvents(int $scheduleId, array $items): void
    {
        $this->schedules->clearEvents($scheduleId);

        foreach ($items as $item) {
            if (!is_array($item) || (int) ($item['event_id'] ?? 0) <= 0) {
                continue;
            }

            $this->schedules->attachEvent(
                $scheduleId,
                (int) $item['event_id'],
                isset($item['assigned_room_id']) && $item['assigned_room_id'] !== '' ? (int) $item['assigned_room_id'] : null,
                (string) ($item['allocation_status'] ?? 'manual'),
                isset($item['conflict_notes']) && $item['conflict_notes'] !== '' ? (string) $item['conflict_notes'] : null
            );
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
}
