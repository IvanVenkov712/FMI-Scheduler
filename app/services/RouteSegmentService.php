<?php
namespace App\Services;

use App\Repositories\RouteSegmentRepository;

class RouteSegmentService extends BaseService
{
    private $route_segment_repository;

    public function __construct($route_segment_repository = null)
    {
        $this->route_segment_repository = $this->dependency($route_segment_repository, RouteSegmentRepository::class);
    }

    public function resolve_route_segment($route_item)
    {
        if (is_numeric($route_item)) {
            return $this->route_segment_repository->find_by_id($route_item);
        }

        if (is_array($route_item)) {
            $segment_id = $route_item['route_segment_id'] ?? $route_item['segment_id'] ?? $route_item['id'] ?? null;
            return is_numeric($segment_id) ? $this->route_segment_repository->find_by_id($segment_id) : null;
        }

        if (is_object($route_item)) {
            if (
                property_exists($route_item, 'id')
                && property_exists($route_item, 'from_room_id')
                && property_exists($route_item, 'to_room_id')
                && property_exists($route_item, 'distance')
            ) {
                return $route_item;
            }

            $segment_id = $route_item->route_segment_id ?? $route_item->segment_id ?? $route_item->id ?? null;
            return is_numeric($segment_id) ? $this->route_segment_repository->find_by_id($segment_id) : null;
        }

        return null;
    }

}
