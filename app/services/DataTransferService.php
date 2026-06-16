<?php
namespace App\Services;

use App\Repositories\BuildingRepository;
use App\Repositories\EventRepository;
use App\Repositories\FloorRepository;
use App\Repositories\ResourceRepository;
use App\Repositories\RoomRepository;
use App\Repositories\ScheduleRepository;

abstract class DataTransferService extends BaseService
{
    protected $building_repository;
    protected $floor_repository;
    protected $room_repository;
    protected $event_repository;
    protected $schedule_repository;
    protected $resource_repository;
    protected $export_directory;

    public function __construct(
        $building_repository = null,
        $floor_repository = null,
        $room_repository = null,
        $event_repository = null,
        $schedule_repository = null,
        $resource_repository = null,
        $export_directory = null
    ) {
        $this->building_repository = $this->dependency($building_repository, BuildingRepository::class);
        $this->floor_repository = $this->dependency($floor_repository, FloorRepository::class);
        $this->room_repository = $this->dependency($room_repository, RoomRepository::class);
        $this->event_repository = $this->dependency($event_repository, EventRepository::class);
        $this->schedule_repository = $this->dependency($schedule_repository, ScheduleRepository::class);
        $this->resource_repository = $this->dependency($resource_repository, ResourceRepository::class);
        $this->export_directory = $export_directory ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'exports';
    }
}
