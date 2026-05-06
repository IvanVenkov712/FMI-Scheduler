<?php
namespace App\Services;

use App\Helpers\ResourceRequirementsHelper;
use App\Repositories\ResourceRepository;
use App\Repositories\RoomRepository;

class RoomResourceService
{
    private $room_repository;
    private $resource_repository;

    public function __construct($room_repository = null, $resource_repository = null)
    {
        $this->room_repository = $room_repository ?? new RoomRepository();
        $this->resource_repository = $resource_repository ?? new ResourceRepository();
    }

    public function sync_room_resource($roomId, $resourceId, $quantity)
    {
        if (!$this->has_valid_resource_target($roomId, $resourceId)) {
            return false;
        }

        if (!is_numeric($quantity)) {
            return false;
        }

        $quantity = (int) $quantity;
        if ($quantity < 0) {
            return false;
        }

        $current_resource = $this->find_room_resource($roomId, $resourceId);
        if ($current_resource !== null && (int) $current_resource->resource_quantity === $quantity) {
            return true;
        }

        return $this->room_repository->update_resource($roomId, $resourceId, $quantity);
    }

    public function room_meets_requirements($roomId, $requirements)
    {
        $room = $this->room_repository->find_by_id($roomId);
        if ($room === null || !$room->is_active) {
            return false;
        }

        $normalized_requirements = ResourceRequirementsHelper::normalize_requirements($requirements);
        if ($normalized_requirements === null) {
            return false;
        }

        return $this->room_repository->has_available_resources($roomId, $normalized_requirements);
    }

    public function remove_resource($roomId, $resourceId)
    {
        if (!$this->has_valid_resource_target($roomId, $resourceId)) {
            return false;
        }

        if ($this->find_room_resource($roomId, $resourceId) === null) {
            return false;
        }

        return $this->room_repository->update_resource($roomId, $resourceId, 0);
    }

    private function has_valid_resource_target($room_id, $resource_id)
    {
        if (!is_numeric($room_id) || !is_numeric($resource_id)) {
            return false;
        }

        return $this->room_repository->exists($room_id)
            && $this->resource_repository->exists($resource_id);
    }

    private function find_room_resource($room_id, $resource_id)
    {
        foreach ($this->room_repository->get_available_resources($room_id) as $room_resource) {
            if ((int) $room_resource->resource_id === (int) $resource_id) {
                return $room_resource;
            }
        }

        return null;
    }
}
