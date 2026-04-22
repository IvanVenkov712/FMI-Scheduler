<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EventServiceTest extends TestCase
{
    public function testAssignRoomRejectsInsufficientRequiredResourceQuantity(): void
    {
        $event = new TestEventForEventService([
            'id' => 41,
            'students_count' => 35,
            'starts_at' => '2026-06-10 09:00:00',
            'ends_at' => '2026-06-10 11:00:00',
            'required_resources' => json_encode([
                ['resource_id' => 8, 'quantity' => 2],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $room = new TestRoomForEventService([
            'id' => 12,
            'capacity' => 60,
        ], [
            new RoomResource(['resource_id' => 8, 'quantity' => 1, 'status' => 'available']),
        ]);

        $service = new EventService(
            new FakeEventRepositoryForEventService($event),
            new FakeRoomRepositoryForEventService($room),
            new AuditLogRepository(),
            new ValidationService()
        );

        $result = $service->assignRoom(41, 12);

        $this->assertFalse($result['success']);
        $this->assertSame(
            'Room resources are insufficient for the event requirements.',
            $result['errors']['room'][0]
        );
    }
}

final class FakeEventRepositoryForEventService extends EventRepository
{
    public function __construct(
        private readonly ?Event $event
    ) {
    }

    public function find(int|string $id): ?Event
    {
        return $this->event;
    }

    public function overlapsForRoom(int $roomId, string $startsAt, string $endsAt, ?int $ignoreEventId = null): array
    {
        return [];
    }
}

final class FakeRoomRepositoryForEventService extends RoomRepository
{
    public function __construct(
        private readonly ?Room $room
    ) {
    }

    public function find(int|string $id): ?Room
    {
        return $this->room;
    }
}

final class TestEventForEventService extends Event
{
}

final class TestRoomForEventService extends Room
{
    public function __construct(
        array $attributes,
        private readonly array $resources
    ) {
        parent::__construct($attributes);
    }

    public function resources(): array
    {
        return $this->resources;
    }
}
