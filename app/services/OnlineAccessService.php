<?php
namespace App\Services;

use App\Helpers\EventDeliveryModeHelper;
use App\Helpers\OnlineAccessHelper;
use App\Repositories\EventRepository;
use App\Repositories\OnlineAccessRepository;
use App\Repositories\RoomRepository;

class OnlineAccessService
{
    private $event_repository;
    private $online_access_repository;
    private $room_repository;

    public function __construct(
        $event_repository = null,
        $online_access_repository = null,
        $room_repository = null,
    ) {
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->online_access_repository = $online_access_repository ?? new OnlineAccessRepository();
        $this->room_repository = $room_repository ?? new RoomRepository();
    }

    public function provision_online_access($event_id, $platform, $link, $key = null, $host_notes = '')
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$this->event_can_have_online_access($event)) {
            return null;
        }

        $online_access = OnlineAccessHelper::build($platform, $link, $key, $host_notes, true);
        if (!OnlineAccessHelper::is_valid($online_access)) {
            return null;
        }

        if ($event->online_access_id !== null) {
            $existing_online_access = $this->online_access_repository->find_by_id($event->online_access_id);
            if ($existing_online_access === null) {
                return null;
            }

            $online_access->id = $existing_online_access->id;
            $this->online_access_repository->update($existing_online_access->id, $online_access);

            return $this->online_access_repository->find_by_id($existing_online_access->id);
        }

        $this->event_repository->upsert_online_access($event->id, $online_access);

        return $this->event_repository->get_online_access($event->id);
    }

    public function rotate_access_key($event_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$this->event_can_have_online_access($event) || $event->online_access_id === null) {
            return null;
        }

        $online_access = $this->online_access_repository->find_by_id($event->online_access_id);
        if ($online_access === null || !OnlineAccessHelper::is_valid($online_access)) {
            return null;
        }

        $online_access->access_key = OnlineAccessHelper::generate_access_key();
        $this->online_access_repository->update($online_access->id, $online_access);

        return $this->online_access_repository->find_by_id($online_access->id);
    }

    public function validate_hybrid_event_setup($event_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || EventDeliveryModeHelper::normalize($event->delivery_mode) !== 'hybrid') {
            return false;
        }

        if ($event->room_id === null || !is_numeric($event->room_id)) {
            return false;
        }

        $room = $this->room_repository->find_by_id($event->room_id);
        if ($room === null || !$room->is_active) {
            return false;
        }

        if (!EventDeliveryModeHelper::is_room_type_compatible($event->delivery_mode, $room->room_type)) {
            return false;
        }

        if ($event->online_access_id === null) {
            return false;
        }

        $online_access = $this->online_access_repository->find_by_id($event->online_access_id);

        return OnlineAccessHelper::is_valid($online_access);
    }

    private function event_can_have_online_access($event)
    {
        return EventDeliveryModeHelper::supports_online_access($event->delivery_mode);
    }
}
