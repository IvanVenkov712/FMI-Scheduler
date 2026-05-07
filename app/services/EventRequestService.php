<?php
namespace App\Services;

use App\Helpers\ResourceRequirementsHelper;
use App\Helpers\EventDeliveryModeHelper;
use App\Helpers\OnlineAccessHelper;
use App\Helpers\TimeHelper;
use App\Repositories\OnlineAccessRepository;
use App\Repositories\EventRepository;

class EventRequestService extends BaseService
{
    private $event_repository;
    private $online_access_repository;
    private $room_availability_service;

    public function __construct(
        $event_repository = null,
        $online_access_repository = null,
        $room_availability_service = null,
    ) {
        $this->event_repository = $this->dependency($event_repository, EventRepository::class);
        $this->online_access_repository = $this->dependency($online_access_repository, OnlineAccessRepository::class);
        $this->room_availability_service = $this->dependency($room_availability_service, RoomAvailabilityService::class, null, $this->event_repository);
    }

    public function submit_event_request($event, $requirements = [], $online_access = null)
    {
        $requirements = ResourceRequirementsHelper::normalize_requirements($requirements);

        if (!$this->is_valid_request_event($event) || $requirements === null) {
            return null;
        }

        if (!$this->is_request_bookable($event, $requirements)) {
            return null;
        }

        if (!$this->is_online_access_valid_for_event($event, $online_access)) {
            return null;
        }

        $event->status = 'pending';
        $event->online_access_id = null;
        $event_id = $this->transaction($this->event_repository, function () use ($event, $requirements, $online_access) {
            $event_id = (int) $this->event_repository->insert($event);
            $this->event_repository->replace_resource_requirements($event_id, $requirements);

            if ($online_access !== null) {
                $this->event_repository->upsert_online_access($event_id, $online_access);
            }

            return $event_id;
        });

        return $this->refresh($this->event_repository, $event_id);
    }

    public function edit_pending_request($event_id, $new_event, $new_requirements = [], $new_online_access = null)
    {
        $existing_event = $this->event_repository->find_by_id($event_id);
        $new_requirements = ResourceRequirementsHelper::normalize_requirements($new_requirements);

        if (!$this->can_proceed($existing_event) || !$this->is_valid_request_event($new_event)) {
            return null;
        }

        if ($new_requirements === null || !$this->is_request_bookable($new_event, $new_requirements)) {
            return null;
        }

        if (!$this->is_online_access_valid_for_event($new_event, $new_online_access)) {
            return null;
        }

        $new_event->status = 'pending';
        $new_event->online_access_id = $existing_event->online_access_id;

        $this->transaction($this->event_repository, function () use ($event_id, $new_event, $new_requirements, $new_online_access) {
            $this->event_repository->update($event_id, $new_event);
            $this->event_repository->replace_resource_requirements($event_id, $new_requirements);

            if ($new_online_access === null) {
                $this->delete_online_access($event_id);
            } else {
                $this->event_repository->upsert_online_access($event_id, $new_online_access);
            }

        });

        return $this->refresh($this->event_repository, $event_id);
    }

    public function attach_resource_requirements($event_id, $resource_requirements)
    {
        $event = $this->event_repository->find_by_id($event_id);
        $resource_requirements = ResourceRequirementsHelper::normalize_requirements($resource_requirements);

        if (!$this->can_proceed($event)) {
            return false;
        }

        if ($resource_requirements === null || !$this->is_request_bookable($event, $resource_requirements)) {
            return false;
        }

        $this->event_repository->replace_resource_requirements($event_id, $resource_requirements);
        return true;
    }

    public function attach_online_access($event_id, $online_access)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if (!$this->can_proceed($event)) {
            return null;
        }

        if ($online_access === null) {
            return null;
        }

        if (!$this->is_online_access_valid_for_event($event, $online_access)) {
            return null;
        }

        if ($event->online_access_id === null) {
            $this->event_repository->upsert_online_access($event_id, $online_access);
        } else {
            $online_access->id = $event->online_access_id;
            $this->online_access_repository->update($event->online_access_id, $online_access);
        }

        return $this->event_repository->get_online_access($event_id);
    }

    private function is_request_bookable($event, $resource_requirements)
    {
        if (EventDeliveryModeHelper::is_online($event->delivery_mode)) {
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
        if (!EventDeliveryModeHelper::online_access_matches($event->delivery_mode, $online_access)) {
            return false;
        }

        return $online_access === null || OnlineAccessHelper::is_valid($online_access);
    }

    private function delete_online_access($event_id)
    {
        $online_access_id = $this->event_repository->detach_online_access($event_id);
        if ($online_access_id !== null) {
            $this->online_access_repository->delete($online_access_id);
        }
    }

    private function can_proceed($event)
    {
        return $event !== null && $event->is_pending();
    }

    private function is_valid_request_event($event)
    {
        if ($event === null || !is_object($event)) {
            return false;
        }

        if (!is_numeric($event->requestor_id ?? null)) {
            return false;
        }

        if (trim((string) ($event->name ?? '')) === '') {
            return false;
        }

        if (!is_numeric($event->student_count ?? null) || (int) $event->student_count < 0) {
            return false;
        }

        if (!TimeHelper::is_valid_interval($event->start_time ?? null, $event->end_time ?? null)) {
            return false;
        }

        return EventDeliveryModeHelper::is_valid($event->delivery_mode ?? '');
    }
}
