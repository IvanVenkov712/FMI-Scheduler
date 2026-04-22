<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouteServiceTest extends TestCase
{
    public function testRecommendedReturnsResolvedConnectedRouteWithSteps(): void
    {
        $resolvedRoute = ResolvedRoute::fromArray([
            'from_room_id' => 1,
            'to_room_id' => 3,
            'name' => 'Resolved route A101 -> B201',
            'walking_duration_minutes' => 7,
            'status' => 'active',
            'path_data' => json_encode([
                'resolved' => true,
                'segment_count' => 2,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'resolved_steps' => [
                ['step_order' => 1, 'instruction' => 'Move from room A101 to room A102 via corridor.', 'duration_minutes' => 3],
                ['step_order' => 2, 'instruction' => 'Move from room A102 to room B201 via stairs.', 'duration_minutes' => 4],
            ],
            'resolved_segment_count' => 2,
        ]);

        $service = new RouteService(
            new FakeRouteRepositoryForService([$resolvedRoute]),
            new AuditLogRepository(),
            new ValidationService()
        );

        $recommended = $service->recommended(1, 3, false, 'walking');

        $this->assertNotNull($recommended);
        $this->assertCount(2, $recommended['steps']);
        $this->assertStringContainsString('best connected path', $recommended['explanation']);
        $this->assertStringContainsString('2 segments', $recommended['explanation']);
    }
}

final class FakeRouteRepositoryForService extends RouteRepository
{
    public function __construct(
        private readonly array $routes
    ) {
    }

    public function betweenRooms(int $fromRoomId, int $toRoomId, bool $accessibleOnly = false, string $movementType = ''): array
    {
        return $this->routes;
    }
}
