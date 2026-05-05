<?php
namespace App\Services;

use App\Core\Database;
use App\models\Event;
use App\models\OnlineAccess;
use App\Repositories\OnlineAccessRepository;
use App\Repositories\EventRepository;
use App\Repositories\ScheduleRepository;

class EventRequestService
{
    private $event_repository;
    private $schedule_repository;
    private $online_access_repository;
    private $room_availability_service;

    public function __construct(
        $event_repository = null,
        $schedule_repository = null,
        $online_access_repository = null,
        $room_availability_service = null,
    ) {
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->schedule_repository = $schedule_repository ?? new ScheduleRepository();
        $this->online_access_repository = $online_access_repository ?? new OnlineAccessRepository();
        $this->room_availability_service = $room_availability_service ?? new RoomAvailabilityService(null, $this->event_repository);
    }

    public function submit_event_request($event, $requirements = null, $online_access=null)
    {
        if ($event === null) {
            return null;
        }

        $event_id = (int) $this->event_repository->insert($event);
        $this->replace_resource_requirements($event_id, $requirements);

        if ($online_access !== null) {
            $this->upsert_online_access($event_id, $online_access);
        }

        return $this->event_repository->find_by_id($event_id);
    }

    public function edit_pending_request($event_id, $new_event, $new_requirements, $new_online_access)
    {
        $existing_event = $this->event_repository->find_by_id($event_id);
        if ($existing_event === null || !$this->is_pending($existing_event)) {
            return null;
        }
        if ($new_requirements === null || !$this->is_request_bookable($new_event, $new_requirements)) {
            return null;
        }
        $this->event_repository->update($event_id, $new_event);
        if ($new_requirements !== null) {
            $this->replace_resource_requirements($event_id, $new_requirements);
        }
        if ($new_online_access === null) {
            $this->delete_online_access($event_id);
        } else {
            $this->upsert_online_access($event_id, $new_online_access);
        }
        return $this->event_repository->find_by_id($event_id);
    }

    public function attach_resource_requirements($event_id, $resource_requirements)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$this->is_pending($event)) {
            return false;
        }

        $requirements = $this->normalize_resource_requirements($resource_requirements);
        if ($requirements === null || !$this->is_request_bookable($event, $requirements)) {
            return false;
        }
        $this->replace_resource_requirements($event_id, $requirements);
    }

    public function attach_online_access($event_id, $online_access)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$this->is_pending($event)) {
            return null;
        }

        if ($online_access === null || !$this->is_online_access_valid_for_event($event, $online_access)) {
            return null;
        }

        $this->upsert_online_access($event_id, $online_access);
        return $this->event_repository->get_online_access($event_id);
    }

    private function is_request_bookable($event, $resource_requirements)
    {
        if ($event->delivery_mode === 'online') {
            return true;
        }

        return $this->room_availability_service->is_room_bookable(
            $event->room_id,
            $event->start_time,
            $event->end_time,
            $event->student_count,
            $resource_requirements
        );
    }

    private function is_online_access_valid_for_event($event, $online_access)
    {
        if ($event->delivery_mode === 'physical') {
            return $online_access === null;
        }

        return true;
    }

    private function delete_online_access($event_id)
    {
        $existing_online_access = $this->event_repository->get_online_access($event_id);
        if ($existing_online_access !== null) {
            $this->online_access_repository->delete($existing_online_access->id);
        }
    }

    private function is_pending($event)
    {
        return strtolower(trim((string) $event->status)) === 'pending';
    }
}
