<?php
namespace App\Services;

use App\Helpers\EventDeliveryModeHelper;
use App\Helpers\TimeHelper;
use App\Repositories\EventRepository;
use App\Repositories\RoomRepository;

class RoomAllocationService extends BaseService
{
    private $event_repository;
    private $room_repository;
    private $room_availability_service;

    public function __construct(
        $event_repository = null,
        $room_repository = null,
        $room_availability_service = null,
    ) {
        $this->event_repository = $this->dependency($event_repository, EventRepository::class);
        $this->room_repository = $this->dependency($room_repository, RoomRepository::class);
        $this->room_availability_service = $room_availability_service
            ?? new RoomAvailabilityService($this->room_repository, $this->event_repository);
    }

    public function assign_best_room($event_id)
    {
        $ranked_rooms = $this->rank_candidate_rooms($event_id);
        if ($ranked_rooms === []) {
            return null;
        }

        $best_room = $ranked_rooms[0]['room'];
        return $this->assign_room($event_id, $best_room->id);
    }

    public function reassign_room($event_id, $preferred_room_id = null)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if (!$this->can_allocate_room($event)) {
            return null;
        }

        $resource_requirements = $this->event_repository->get_resource_requirements($event_id);
        $preferred_room = $this->room_repository->find_by_id($preferred_room_id);

        if ($this->is_candidate_room($event, $preferred_room, $resource_requirements)) {
            return $this->assign_room($event_id, $preferred_room_id);
        }

        return $this->assign_best_room($event_id);
    }

    /**
     * Ranks eligible rooms by spare capacity, then capacity and room ID.
     * Only this event is considered; this is not a timetable-wide optimizer.
     *
     * @return array<int, array{room: object, score: int, capacity_surplus: int}>
     */
    public function rank_candidate_rooms($event_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if (!$this->can_allocate_room($event)) {
            return [];
        }

        $resource_requirements = $this->event_repository->get_resource_requirements($event_id);
        $candidate_rooms = [];

        foreach ($this->room_repository->find_all() as $room) {
            if (!$this->is_candidate_room($event, $room, $resource_requirements)) {
                continue;
            }

            $capacity_surplus = (int) $room->capacity - (int) $event->student_count;

            $candidate_rooms[] = [
                'room' => $room,
                'score' => $capacity_surplus,
                'capacity_surplus' => $capacity_surplus,
            ];
        }

        usort($candidate_rooms, function ($left, $right) {
            $score_comparison = $left['score'] <=> $right['score'];
            if ($score_comparison !== 0) {
                return $score_comparison;
            }

            $capacity_comparison = $left['room']->capacity <=> $right['room']->capacity;
            if ($capacity_comparison !== 0) {
                return $capacity_comparison;
            }

            return $left['room']->id <=> $right['room']->id;
        });

        return $candidate_rooms;
    }

    private function assign_room($event_id, $room_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null) {
            return null;
        }

        $event->room_id = $room_id;
        return $this->update_and_refresh($this->event_repository, $event_id, $event);
    }

    private function can_allocate_room($event)
    {
        if ($event === null || !TimeHelper::is_valid_interval($event->start_time, $event->end_time)) {
            return false;
        }

        if ((int) $event->student_count < 0) {
            return false;
        }

        return !EventDeliveryModeHelper::is_online($event->delivery_mode);
    }

    private function is_candidate_room($event, $room, $resource_requirements)
    {
        if ($room === null || !$room->is_active) {
            return false;
        }

        if (!$this->is_room_type_compatible($event->delivery_mode, $room->room_type)) {
            return false;
        }

        if ((int) $room->capacity < (int) $event->student_count) {
            return false;
        }

        if ($resource_requirements === null) {
            return false;
        }

        if (!$this->room_repository->has_available_resources($room->id, $resource_requirements)) {
            return false;
        }

        foreach ($this->room_availability_service->find_conflicting_events($room->id, $event->start_time, $event->end_time) as $conflict) {
            if ((int) $conflict->id !== (int) $event->id) {
                return false;
            }
        }

        return true;
    }

    private function is_room_type_compatible($delivery_mode, $room_type)
    {
        return EventDeliveryModeHelper::is_room_type_compatible($delivery_mode, $room_type);
    }
}
