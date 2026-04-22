<?php

declare(strict_types=1);

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboard = new DashboardRepository()
    ) {
    }

    public function overview(): array
    {
        return [
            'stats' => $this->dashboard->stats(),
            'upcoming_events' => $this->dashboard->upcomingEvents(),
            'pending_requests' => $this->dashboard->pendingRequests(),
        ];
    }
}
