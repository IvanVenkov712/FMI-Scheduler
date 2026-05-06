<?php
namespace App\Services;

use App\Helpers\StringHelper;
use App\models\OnlineAccess;
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

    public function provision_online_access($eventId, $platform, $link, $key)
    {
        $event = $this->event_repository->find_by_id($eventId);
        if ($event === null || !$this->event_can_have_online_access($event)) {
            return null;
        }

        $online_access = $this->build_online_access($platform, $link, $key);
        if (!$this->is_valid_online_access($online_access)) {
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

    public function rotate_access_key($eventId)
    {
        $event = $this->event_repository->find_by_id($eventId);
        if ($event === null || !$this->event_can_have_online_access($event) || $event->online_access_id === null) {
            return null;
        }

        $online_access = $this->online_access_repository->find_by_id($event->online_access_id);
        if ($online_access === null || !$this->is_valid_online_access($online_access)) {
            return null;
        }

        $online_access->access_key = $this->generate_access_key();
        $this->online_access_repository->update($online_access->id, $online_access);

        return $this->online_access_repository->find_by_id($online_access->id);
    }

    public function validate_hybrid_event_setup($eventId)
    {
        $event = $this->event_repository->find_by_id($eventId);
        if ($event === null || StringHelper::normalize_string($event->delivery_mode) !== 'hybrid') {
            return false;
        }

        if ($event->room_id === null || !is_numeric($event->room_id)) {
            return false;
        }

        $room = $this->room_repository->find_by_id($event->room_id);
        if ($room === null || !$room->is_active) {
            return false;
        }

        $room_type = StringHelper::normalize_string($room->room_type);
        if ($room_type !== 'physical' && $room_type !== 'hybrid') {
            return false;
        }

        if ($event->online_access_id === null) {
            return false;
        }

        $online_access = $this->online_access_repository->find_by_id($event->online_access_id);

        return $this->is_valid_online_access($online_access);
    }

    private function event_can_have_online_access($event)
    {
        $delivery_mode = StringHelper::normalize_string($event->delivery_mode);

        return $delivery_mode === 'online' || $delivery_mode === 'hybrid';
    }

    private function build_online_access($platform, $link, $key)
    {
        $online_access = new OnlineAccess();
        $online_access->platform = trim((string) $platform);
        $online_access->meeting_link = trim((string) $link);
        $online_access->access_key = trim((string) $key);

        return $online_access;
    }

    private function is_valid_online_access($online_access)
    {
        if ($online_access === null) {
            return false;
        }

        if (StringHelper::normalize_string($online_access->platform) === '') {
            return false;
        }

        if (trim((string) $online_access->meeting_link) === '') {
            return false;
        }

        return filter_var($online_access->meeting_link, FILTER_VALIDATE_URL) !== false;
    }

    private function generate_access_key()
    {
        return bin2hex(random_bytes(16));
    }
}
