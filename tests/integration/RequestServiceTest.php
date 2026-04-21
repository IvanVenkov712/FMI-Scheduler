<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RequestServiceTest extends TestCase
{
    private InMemoryRequestRepository $requests;
    private RecordingEventService $events;
    private RecordingAuditLogRepository $auditLogs;
    private RequestService $service;

    public function setUp(): void
    {
        $this->requests = new InMemoryRequestRepository();
        $this->events = new RecordingEventService();
        $this->auditLogs = new RecordingAuditLogRepository();
        $this->service = new RequestService(
            $this->requests,
            $this->events,
            $this->auditLogs,
            new ValidationService()
        );
    }

    public function testCreateAssignsDefaultsAndPersistsNormalizedRequest(): void
    {
        $result = $this->service->create([
            'course_name' => 'Databases',
            'students_count' => '120',
            'preferred_room_id' => '15',
            'preferred_delivery_mode' => 'physical',
            'required_resources' => ['projector', 'camera'],
        ], 9);

        $this->assertTrue($result['success']);
        /** @var ExamRequest $request */
        $request = $result['request'];

        $this->assertSame('pending', $request->get('status'));
        $this->assertSame(9, $request->get('requested_by'));
        $this->assertSame(120, $request->get('students_count'));
        $this->assertSame(15, $request->get('preferred_room_id'));
        $this->assertCount(1, $this->auditLogs->records);
        $this->assertSame('create', $this->auditLogs->records[0]['action']);
    }

    public function testCreateRejectsInvalidPreferredDateRange(): void
    {
        $result = $this->service->create([
            'course_name' => 'Algorithms',
            'students_count' => 80,
            'preferred_starts_at' => '2026-05-10 14:00:00',
            'preferred_ends_at' => '2026-05-10 13:00:00',
        ], 4);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('preferred_ends_at', $result['errors']);
        $this->assertCount(0, $this->requests->allStored());
    }

    public function testReviewRejectsInvalidTransitionFromApprovedToRejected(): void
    {
        $existing = $this->requests->seed([
            'course_name' => 'Networks',
            'students_count' => 30,
            'status' => 'approved',
            'requested_by' => 5,
        ]);

        $result = $this->service->review((int) $existing->get('id'), 'rejected', 3, 'No longer needed');

        $this->assertFalse($result['success']);
        $this->assertSame(
            'Invalid workflow transition for this request.',
            $result['errors']['status'][0]
        );
    }

    public function testCreateEventFromApprovedRequestSchedulesEventAndUpdatesRequest(): void
    {
        $request = $this->requests->seed([
            'course_name' => 'Operating Systems',
            'students_count' => 55,
            'status' => 'approved',
            'requested_by' => 7,
            'preferred_room_id' => 12,
            'preferred_delivery_mode' => 'hybrid',
            'preferred_starts_at' => '2026-06-15 09:00:00',
            'preferred_ends_at' => '2026-06-15 11:00:00',
        ]);

        $result = $this->service->createEventFromRequest((int) $request->get('id'), 11);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $this->events->createdPayloads);
        $payload = $this->events->createdPayloads[0];
        $this->assertSame('Exam: Operating Systems', $payload['title']);
        $this->assertSame('requested', $payload['status']);
        $this->assertSame('hybrid', $payload['delivery_mode']);

        /** @var ExamRequest $updatedRequest */
        $updatedRequest = $result['request'];
        $this->assertSame('scheduled', $updatedRequest->get('status'));
        $this->assertSame(501, $updatedRequest->get('event_id'));
        $this->assertSame(11, $updatedRequest->get('reviewed_by'));
        $this->assertCount(1, $this->auditLogs->filterByAction('schedule'));
    }
}

final class InMemoryRequestRepository extends RequestRepository
{
    private array $storage = [];
    private int $nextId = 1;

    public function find(int|string $id): ?ExamRequest
    {
        return isset($this->storage[(int) $id]) ? TestExamRequest::fromArray($this->storage[(int) $id]) : null;
    }

    public function create(array $data): ExamRequest
    {
        $data['id'] = $this->nextId++;
        if (isset($data['required_resources']) && is_array($data['required_resources'])) {
            $data['required_resources'] = json_encode($data['required_resources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $this->storage[(int) $data['id']] = $data;

        return TestExamRequest::fromArray($data);
    }

    public function update(int|string $id, array $data): ?ExamRequest
    {
        $existing = $this->storage[(int) $id] ?? null;
        if ($existing === null) {
            return null;
        }

        if (isset($data['required_resources']) && is_array($data['required_resources'])) {
            $data['required_resources'] = json_encode($data['required_resources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $this->storage[(int) $id] = $existing + $data;
        foreach ($data as $key => $value) {
            $this->storage[(int) $id][$key] = $value;
        }

        return TestExamRequest::fromArray($this->storage[(int) $id]);
    }

    public function review(int|string $id, string $status, ?int $reviewedBy = null, ?string $notes = null): ?ExamRequest
    {
        $payload = [
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => '2026-04-21 12:00:00',
        ];

        if ($notes !== null) {
            $payload['notes'] = $notes;
        }

        return $this->update($id, $payload);
    }

    public function delete(int|string $id): bool
    {
        if (!isset($this->storage[(int) $id])) {
            return false;
        }

        unset($this->storage[(int) $id]);

        return true;
    }

    public function seed(array $data): ExamRequest
    {
        return $this->create($data);
    }

    public function allStored(): array
    {
        return $this->storage;
    }
}

final class RecordingEventService extends EventService
{
    public array $createdPayloads = [];

    public function __construct()
    {
    }

    public function create(array $data, ?array $onlineSession = null, ?int $userId = null): array
    {
        $this->createdPayloads[] = $data;

        return [
            'success' => true,
            'event' => TestEvent::fromArray($data + ['id' => 501]),
        ];
    }
}

final class RecordingAuditLogRepository extends AuditLogRepository
{
    public array $records = [];

    public function __construct()
    {
    }

    public function record(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        $this->records[] = [
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old' => $oldValues,
            'new' => $newValues,
            'user_id' => $userId,
        ];

        return new AuditLog([
            'id' => count($this->records),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
        ]);
    }

    public function filterByAction(string $action): array
    {
        return array_values(array_filter(
            $this->records,
            static fn (array $record): bool => $record['action'] === $action
        ));
    }
}

final class TestExamRequest extends ExamRequest
{
    public static function fromArray(array $attributes): self
    {
        $model = new self($attributes);
        $model->attributes = $attributes;

        return $model;
    }
}

final class TestEvent extends Event
{
    public static function fromArray(array $attributes): self
    {
        $model = new self($attributes);
        $model->attributes = $attributes;

        return $model;
    }
}
