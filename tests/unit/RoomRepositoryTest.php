<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RoomRepositoryTest extends TestCase
{
    public function testAvailableBetweenRequiresMinimumResourceQuantity(): void
    {
        $database = new CapturingRoomRepositoryDatabase();
        $repository = new RoomRepository($database);

        $repository->availableBetween(
            40,
            '2026-06-01 09:00:00',
            '2026-06-01 11:00:00',
            'physical',
            [['resource_id' => 7, 'quantity' => 2]]
        );

        $this->assertNotNull($database->lastSql);
        $this->assertStringContainsString('room_resources.quantity >= :resource_quantity_0', $database->lastSql);
        $this->assertSame(7, $database->lastParams['resource_0']);
        $this->assertSame(2, $database->lastParams['resource_quantity_0']);
    }
}

final class CapturingRoomRepositoryDatabase extends Database
{
    public ?string $lastSql = null;
    public array $lastParams = [];

    public function __construct()
    {
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;

        return [];
    }
}
