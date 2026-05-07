<?php
namespace App\Controllers;

use App\Repositories\BuildingRepository;
use App\Repositories\EventRepository;
use App\Repositories\RoomRepository;
use App\Repositories\ScheduleRepository;

class DashboardController extends BaseController
{
    public function index()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $building_repository = new BuildingRepository();
        $room_repository = new RoomRepository();
        $event_repository = new EventRepository();
        $schedule_repository = new ScheduleRepository();

        return $this->respond('dashboard/index', [
            'stats' => [
                'buildings' => count($building_repository->find_all()),
                'rooms' => count($room_repository->find_all()),
                'events' => count($event_repository->find_all()),
                'schedules' => count($schedule_repository->find_all()),
            ],
            'events' => $event_repository->find_all(),
        ]);
    }
}
