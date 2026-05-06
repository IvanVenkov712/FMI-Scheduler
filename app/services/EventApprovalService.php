<?php
namespace App\Services;

use App\Helpers\StringHelper;
use App\Repositories\EventRepository;

class EventApprovalService
{
    private $event_repository;
    private $room_availability_service;

    public function __construct(
        $event_repository = null,
        $room_availability_service = null,
    ) {
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->room_availability_service = $room_availability_service ?? new RoomAvailabilityService(null, $this->event_repository);
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
        $this->event_repository->update($event_id, $event);

        return $this->event_repository->find_by_id($event_id);
    }

    public function reject_event_request($event_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$event->is_pending()) {
            return null;
        }

        $event->status = 'rejected';
        $this->event_repository->update($event_id, $event);

        return $this->event_repository->find_by_id($event_id);
    }

    public function reschedule_approved_event($event_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$event->is_approved()) {
            return null;
        }

        $event->status = 'pending';
        $this->event_repository->update($event_id, $event);

        return $this->event_repository->find_by_id($event_id);
    }

    private function can_approve($event)
    {
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
        return StringHelper::normalize_string($event->delivery_mode) === 'online';
    }
}
