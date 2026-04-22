<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ScheduleServiceTest extends TestCase
{
    public function testAttachEventRejectsEventOutsideSchedulePeriod(): void
    {
        $schedule = new Schedule([
            'id' => 10,
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
        ]);
        $event = new Event([
            'id' => 25,
            'starts_at' => '2026-07-01 10:00:00',
            'ends_at' => '2026-07-01 12:00:00',
            'delivery_mode' => 'physical',
            'room_id' => 5,
        ]);

        $repository = new FakeScheduleRepositoryForService($schedule);
        $service = new ScheduleService(
            $repository,
            new FakeEventRepositoryForScheduleService($event),
            new FakeEventServiceForScheduleService(),
            new AuditLogRepository(),
            new ValidationService()
        );

        $result = $service->attachEvent(10, 25, 5, 'manual');

        $this->assertFalse($result['success']);
        $this->assertSame('Event falls outside the schedule period.', $result['errors']['event'][0]);
        $this->assertCount(0, $repository->attached);
    }

    public function testAttachEventRejectsManualPhysicalEntryWithoutRoom(): void
    {
        $schedule = new Schedule([
            'id' => 10,
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
        ]);
        $event = new Event([
            'id' => 25,
            'starts_at' => '2026-06-10 10:00:00',
            'ends_at' => '2026-06-10 12:00:00',
            'delivery_mode' => 'physical',
            'room_id' => null,
        ]);

        $repository = new FakeScheduleRepositoryForService($schedule);
        $service = new ScheduleService(
            $repository,
            new FakeEventRepositoryForScheduleService($event),
            new FakeEventServiceForScheduleService(),
            new AuditLogRepository(),
            new ValidationService()
        );

        $result = $service->attachEvent(10, 25, null, 'manual');

        $this->assertFalse($result['success']);
        $this->assertSame(
            'Manual or auto-assigned schedule entries require a room for non-online events.',
            $result['errors']['assigned_room_id'][0]
        );
        $this->assertCount(0, $repository->attached);
    }

    public function testAttachEventSynchronizesAssignedRoomThroughEventService(): void
    {
        $schedule = new Schedule([
            'id' => 10,
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
        ]);
        $event = new Event([
            'id' => 25,
            'starts_at' => '2026-06-10 10:00:00',
            'ends_at' => '2026-06-10 12:00:00',
            'delivery_mode' => 'physical',
            'room_id' => 5,
        ]);

        $eventRepository = new FakeEventRepositoryForScheduleService($event);
        $eventService = new FakeEventServiceForScheduleService(function () use ($eventRepository): void {
            $eventRepository->currentEvent = new Event([
                'id' => 25,
                'starts_at' => '2026-06-10 10:00:00',
                'ends_at' => '2026-06-10 12:00:00',
                'delivery_mode' => 'physical',
                'room_id' => 9,
            ]);
        });
        $repository = new FakeScheduleRepositoryForService($schedule);

        $service = new ScheduleService(
            $repository,
            $eventRepository,
            $eventService,
            new AuditLogRepository(),
            new ValidationService()
        );

        $result = $service->attachEvent(10, 25, 9, 'manual');

        $this->assertTrue($result['success']);
        $this->assertSame([[25, 9, null]], $eventService->assignments);
        $this->assertCount(1, $repository->attached);
        $this->assertSame(9, $repository->attached[0]['assigned_room_id']);
        $this->assertSame('manual', $repository->attached[0]['allocation_status']);
    }
}

final class FakeScheduleRepositoryForService extends ScheduleRepository
{
    public array $attached = [];

    public function __construct(
        private readonly ?Schedule $schedule
    ) {
    }

    public function find(int|string $id): ?Schedule
    {
        return $this->schedule;
    }

    public function attachEvent(
        int $scheduleId,
        int $eventId,
        ?int $assignedRoomId = null,
        string $allocationStatus = 'manual',
        ?string $conflictNotes = null
    ): void {
        $this->attached[] = [
            'schedule_id' => $scheduleId,
            'event_id' => $eventId,
            'assigned_room_id' => $assignedRoomId,
            'allocation_status' => $allocationStatus,
            'conflict_notes' => $conflictNotes,
        ];
    }
}

final class FakeEventRepositoryForScheduleService extends EventRepository
{
    public function __construct(
        public ?Event $currentEvent
    ) {
    }

    public function find(int|string $id): ?Event
    {
        return $this->currentEvent;
    }
}

final class FakeEventServiceForScheduleService extends EventService
{
    public array $assignments = [];

    public function __construct(
        private readonly ?Closure $onAssign = null
    ) {
    }

    public function assignRoom(int $eventId, int $roomId, ?int $userId = null): array
    {
        $this->assignments[] = [$eventId, $roomId, $userId];

        if ($this->onAssign instanceof Closure) {
            ($this->onAssign)();
        }

        return ['success' => true];
    }
}
