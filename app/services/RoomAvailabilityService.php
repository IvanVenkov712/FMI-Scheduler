<?php
namespace App\Services;

use App\Helpers\StringHelper;
use App\Helpers\TimeHelper;
use App\Helpers\ResourceRequirementsHelper;
use App\Repositories\RoomRepository;
use App\Repositories\EventRepository;

class RoomAvailabilityService extends BaseService
{
    private $room_repository;
    private $event_repository;

    public function __construct($room_repository = null, $event_repository = null)
    {
        $this->room_repository = $this->dependency($room_repository, RoomRepository::class);
        $this->event_repository = $this->dependency($event_repository, EventRepository::class);
    }

    public function is_room_bookable($room_id, $start_time, $end_time, $student_count, $resource_requirements = [])
    {
        $resource_requirements = ResourceRequirementsHelper::normalize_requirements($resource_requirements);

        if (!TimeHelper::is_valid_interval($start_time, $end_time)) {
            return false;
        }

        if (!is_numeric($student_count)) {
            return false;
        }

        $student_count = (int) $student_count;

        $room = $this->room_repository->find_by_id($room_id);
        if ($room === null || !$room->is_active) {
            return false;
        }

        if ($student_count < 0 || (int) $room->capacity < $student_count) {
            return false;
        }

        if ($resource_requirements === null) {
            return false;
        }

        if (!$this->room_repository->has_available_resources($room_id, $resource_requirements)) {
            return false;
        }

        return count($this->find_conflicting_events($room_id, $start_time, $end_time)) === 0;
    }

    public function find_conflicting_events($room_id, $start_time, $end_time)
    {
        if (!TimeHelper::is_valid_interval($start_time, $end_time)) {
            return [];
        }

        $events = $this->event_repository->get_all_events($start_time, $end_time);
        $conflicts = [];

        foreach ($events as $event) {
            if ((int) $event->room_id !== (int) $room_id) {
                continue;
            }

            if (StringHelper::normalize_string($event->status) !== 'approved') {
                continue;
            }

            if (TimeHelper::intervals_overlap($event->start_time, $event->end_time, $start_time, $end_time)) {
                $conflicts[] = $event;
            }
        }

        return $conflicts;
    }

    public function find_eligible_rooms($start_time, $end_time, $student_count, $resource_requirements = [])
    {
        $resource_requirements = ResourceRequirementsHelper::normalize_requirements($resource_requirements);

        if (!TimeHelper::is_valid_interval($start_time, $end_time) || !is_numeric($student_count)) {
            return [];
        }

        $student_count = (int) $student_count;
        if ($student_count < 0 || $resource_requirements === null) {
            return [];
        }

        $eligible_rooms = [];
        foreach ($this->room_repository->find_all() as $room) {
            if (!$room->is_active) {
                continue;
            }

            if ($room->capacity < $student_count) {
                continue;
            }

            if (!$this->room_repository->has_available_resources($room->id, $resource_requirements)) {
                continue;
            }

            if (count($this->find_conflicting_events($room->id, $start_time, $end_time)) > 0) {
                continue;
            }

            $eligible_rooms[] = $room;
        }

        usort($eligible_rooms, function ($left, $right) {
            $capacity_comparison = $left->capacity <=> $right->capacity;
            if ($capacity_comparison !== 0) {
                return $capacity_comparison;
            }

            return $left->id <=> $right->id;
        });

        return $eligible_rooms;
    }
}
