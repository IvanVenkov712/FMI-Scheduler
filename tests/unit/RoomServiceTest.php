<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RoomServiceTest extends TestCase
{
    public function testCanHostEventRejectsInsufficientRequiredResourceQuantity(): void
    {
        $room = new TestRoomForRoomService([
            'id' => 15,
            'capacity' => 120,
        ], true, [
            new RoomResource(['resource_id' => 3, 'quantity' => 1, 'status' => 'available']),
        ]);

        $service = new RoomService(
            new FakeRoomRepositoryForRoomService($room),
            new EventRepository(),
            new AuditLogRepository(),
            new ValidationService()
        );

        $canHost = $service->canHostEvent(
            15,
            '2026-06-01 10:00:00',
            '2026-06-01 12:00:00',
            80,
            [['resource_id' => 3, 'quantity' => 2]]
        );

        $this->assertFalse($canHost);
    }

    public function testCanHostEventAcceptsEnoughRequiredResourceQuantity(): void
    {
        $room = new TestRoomForRoomService([
            'id' => 16,
            'capacity' => 120,
        ], true, [
            new RoomResource(['resource_id' => 3, 'quantity' => 2, 'status' => 'available']),
            new RoomResource(['resource_id' => 5, 'quantity' => 50, 'status' => 'available']),
        ]);

        $service = new RoomService(
            new FakeRoomRepositoryForRoomService($room),
            new EventRepository(),
            new AuditLogRepository(),
            new ValidationService()
        );

        $canHost = $service->canHostEvent(
            16,
            '2026-06-01 10:00:00',
            '2026-06-01 12:00:00',
            80,
            [
                ['resource_id' => 3, 'quantity' => 2],
                ['resource_id' => 5, 'quantity' => 50],
            ]
        );

        $this->assertTrue($canHost);
    }
}

final class FakeRoomRepositoryForRoomService extends RoomRepository
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

final class TestRoomForRoomService extends Room
{
    public function __construct(
        array $attributes,
        private readonly bool $availableBetween,
        private readonly array $resources
    ) {
        parent::__construct($attributes);
    }

    public function isAvailableBetween(string $startsAt, string $endsAt): bool
    {
        return $this->availableBetween;
    }

    public function resources(): array
    {
        return $this->resources;
    }
}
