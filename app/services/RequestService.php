<?php

declare(strict_types=1);

class RequestService
{
    public function __construct(
        private readonly RequestRepository $requests = new RequestRepository(),
        private readonly EventService $events = new EventService(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function all(array $filters = []): array
    {
        return $this->requests->all($filters);
    }

    public function pending(int $limit = 50): array
    {
        return $this->requests->pending($limit);
    }

    public function find(int|string $id): ?ExamRequest
    {
        return $this->requests->find($id);
    }

    public function create(array $data, ?int $userId = null): array
    {
        $data['status'] = $data['status'] ?? 'pending';
        $data['requested_by'] = $data['requested_by'] ?? $userId;
        $data = $this->validation->normalizeIntegers($data, ['requested_by', 'event_id', 'students_count', 'preferred_room_id']);

        $errors = $this->validateRequestData($data);

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $request = $this->requests->create($data);
        $this->auditLogs->record('create', 'exam_request', $request->get('id'), null, $request->toArray(), $userId);

        return ['success' => true, 'request' => $request];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $request = $this->requests->find($id);
        if ($request === null) {
            return ['success' => false, 'errors' => ['request' => ['Request not found.']]];
        }

        if (!$request->canBeReviewed() && isset($data['status']) && $data['status'] !== $request->get('status')) {
            return ['success' => false, 'errors' => ['status' => ['This request can no longer be changed.']]];
        }

        $payload = $this->validation->normalizeIntegers($data, ['requested_by', 'event_id', 'students_count', 'preferred_room_id', 'reviewed_by']);
        $errors = $this->validateRequestData($payload + $request->toArray());

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $old = $request->toArray();
        $updated = $this->requests->update($id, $payload);
        $this->auditLogs->record('update', 'exam_request', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'request' => $updated];
    }

    public function review(int|string $id, string $status, ?int $reviewedBy = null, ?string $notes = null): array
    {
        $request = $this->requests->find($id);
        if ($request === null) {
            return ['success' => false, 'errors' => ['request' => ['Request not found.']]];
        }

        if (!$request->canBeReviewed()) {
            return ['success' => false, 'errors' => ['status' => ['Request cannot be reviewed in its current state.']]];
        }

        if (!in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            return ['success' => false, 'errors' => ['status' => ['Invalid request status.']]];
        }

        $currentStatus = (string) $request->get('status', 'pending');
        if (!$this->canTransition($currentStatus, $status)) {
            return ['success' => false, 'errors' => ['status' => ['Invalid workflow transition for this request.']]];
        }

        $old = $request->toArray();
        $updated = $this->requests->review($id, $status, $reviewedBy, $notes);
        $this->auditLogs->record('review', 'exam_request', $id, $old, $updated?->toArray(), $reviewedBy);

        return ['success' => true, 'request' => $updated];
    }

    public function createEventFromRequest(int|string $id, ?int $userId = null): array
    {
        $request = $this->requests->find($id);
        if ($request === null) {
            return ['success' => false, 'errors' => ['request' => ['Request not found.']]];
        }

        $status = (string) $request->get('status', 'pending');
        if ($status !== 'approved') {
            return ['success' => false, 'errors' => ['request' => ['Only approved requests can be scheduled.']]];
        }

        if ($request->get('event_id') !== null) {
            return ['success' => false, 'errors' => ['request' => ['This request is already linked to an event.']]];
        }

        $eventPayload = [
            'created_by' => $userId ?? $request->get('requested_by'),
            'room_id' => $request->get('preferred_room_id'),
            'title' => 'Exam: ' . $request->get('course_name'),
            'type' => 'exam',
            'course_name' => $request->get('course_name'),
            'students_count' => $request->get('students_count'),
            'starts_at' => $request->get('preferred_starts_at'),
            'ends_at' => $request->get('preferred_ends_at'),
            'delivery_mode' => $request->get('preferred_delivery_mode', 'physical'),
            'status' => 'requested',
        ];

        $eventResult = $this->events->create($eventPayload, null, $userId);
        if (!$eventResult['success']) {
            return $eventResult;
        }

        $event = $eventResult['event'];
        $this->requests->update($id, [
            'event_id' => $event->get('id'),
            'status' => 'scheduled',
            'reviewed_by' => $userId,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->auditLogs->record('schedule', 'exam_request', $id, $request->toArray(), ['event_id' => $event->get('id')], $userId);

        return ['success' => true, 'request' => $this->requests->find($id), 'event' => $event];
    }

    private function validateRequestData(array $data): array
    {
        $result = $this->validation->validate($data, [
            'course_name' => 'required|string|max:190',
            'students_count' => 'required|integer|min:1',
            'preferred_delivery_mode' => 'nullable|in:physical,online,hybrid',
            'preferred_starts_at' => 'nullable|date',
            'preferred_ends_at' => 'nullable|date',
            'status' => 'nullable|in:pending,approved,rejected,scheduled,cancelled',
            'required_resources' => 'nullable|array',
        ]);

        $errors = $result['errors'];
        if (!empty($data['preferred_starts_at']) && !empty($data['preferred_ends_at'])) {
            $errors = array_merge_recursive(
                $errors,
                $this->validation->validateDateRange((string) $data['preferred_starts_at'], (string) $data['preferred_ends_at'], 'preferred_starts_at', 'preferred_ends_at')
            );
        }

        return $errors;
    }

    private function canTransition(string $from, string $to): bool
    {
        $transitions = [
            'pending' => ['approved', 'rejected', 'cancelled'],
            'approved' => ['cancelled'],
        ];

        return in_array($to, $transitions[$from] ?? [], true);
    }
}
