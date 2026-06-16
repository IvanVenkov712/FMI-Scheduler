<?php
namespace App\Controllers;

use App\Repositories\BuildingRepository;
use App\Repositories\EventRepository;
use App\Repositories\RoomRepository;
use App\Repositories\ScheduleRepository;
use Override;

class DashboardController extends BaseController
{
    private $building_repository;
    private $event_repository;
    private $room_repository;
    private $schedule_repository;

    public function __construct($request = [], $view = null, $session = null, $building_repository = null, $event_repository = null, $room_repository = null, $schedule_repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->building_repository = $building_repository;
        $this->room_repository = $room_repository;
        $this->event_repository = $event_repository;
        $this->schedule_repository = $schedule_repository;

    }

    public function index()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        return $this->respond('dashboard/index', [
            'stats' => [
                'buildings' => count($this->building_repository()->find_all()),
                'rooms' => count($this->room_repository()->find_all()),
                'events' => count($this->event_repository()->find_all()),
                'schedules' => count($this->schedule_repository()->find_all()),
            ],
            'events' => $this->event_repository()->find_all(),
        ]);
    }

    private function building_repository()
    {
        return $this->building_repository ??= new BuildingRepository();
    }

    private function event_repository()
    {
        return $this->event_repository ??= new EventRepository();
    }

    private function room_repository()
    {
        return $this->room_repository ??= new RoomRepository();
    }

    private function schedule_repository()
    {
        return $this->schedule_repository ??= new ScheduleRepository();
    }
}
