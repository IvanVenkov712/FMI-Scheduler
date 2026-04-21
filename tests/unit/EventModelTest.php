<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EventModelTest extends TestCase
{
    public function testOverlapsDetectsRealIntersectionOnly(): void
    {
        $event = new Event([
            'starts_at' => '2026-06-01 10:00:00',
            'ends_at' => '2026-06-01 12:00:00',
        ]);

        $this->assertTrue($event->overlaps('2026-06-01 11:30:00', '2026-06-01 12:30:00'));
        $this->assertFalse($event->overlaps('2026-06-01 12:00:00', '2026-06-01 13:00:00'));
        $this->assertFalse($event->overlaps('invalid', '2026-06-01 13:00:00'));
    }

    public function testStatusAndDeliveryModeFlagsFollowBusinessRules(): void
    {
        $scheduledHybrid = new Event([
            'status' => 'scheduled',
            'delivery_mode' => 'hybrid',
        ]);

        $draftPhysical = new Event([
            'status' => 'draft',
            'delivery_mode' => 'physical',
        ]);

        $this->assertTrue($scheduledHybrid->isScheduled());
        $this->assertTrue($scheduledHybrid->requiresOnlineSession());
        $this->assertFalse($draftPhysical->isScheduled());
        $this->assertFalse($draftPhysical->requiresOnlineSession());
    }
}
