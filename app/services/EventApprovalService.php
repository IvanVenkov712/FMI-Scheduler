<?php
namespace App\Services;

use App\Helpers\EventDeliveryModeHelper;
use App\Helpers\OnlineAccessHelper;
use App\Repositories\EventRepository;

class EventApprovalService extends BaseService
{
    private $event_repository;
    private $room_availability_service;

    public function __construct(
        $event_repository = null,
        $room_availability_service = null,
    ) {
        $this->event_repository = $this->dependency($event_repository, EventRepository::class);
        $this->room_availability_service = $this->dependency($room_availability_service, RoomAvailabilityService::class, null, $this->event_repository);
    }

    public function approve_event_request($event_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$event->is_pending()) {
            return null;
        }

        if (!$this->can_approve($event)) {
            return null;
        }

        $event->status = 'approved';
        return $this->update_and_refresh($this->event_repository, $event_id, $event);
    }

    public function reject_event_request($event_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$event->is_pending()) {
            return null;
        }

        $event->status = 'rejected';
        return $this->update_and_refresh($this->event_repository, $event_id, $event);
    }

    public function reschedule_approved_event($event_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$event->is_approved()) {
            return null;
        }

        $event->status = 'pending';
        return $this->update_and_refresh($this->event_repository, $event_id, $event);
    }

    private function can_approve($event)
    {
        if (!$this->has_valid_online_setup($event)) {
            return false;
        }

        if ($this->is_online_event($event)) {
            return true;
        }

        $resource_requirements = $this->event_repository->get_resource_requirements($event->id);

        return $this->room_availability_service->is_room_bookable(
            $event->room_id,
            $event->start_time,
            $event->end_time,
            $event->student_count,
            $resource_requirements
        );
    }

    private function is_online_event($event)
    {
        return EventDeliveryModeHelper::is_online($event->delivery_mode);
    }

    private function has_valid_online_setup($event)
    {
        $online_access = $this->event_repository->get_online_access($event->id);

        if (!EventDeliveryModeHelper::online_access_matches($event->delivery_mode, $online_access)) {
            return false;
        }

        return $online_access === null || OnlineAccessHelper::is_valid($online_access);
    }
}
