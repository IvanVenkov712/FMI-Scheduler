<?php
namespace App\Controllers;

use App\Models\RouteSegment;
use App\Repositories\RouteSegmentRepository;
use App\Services\RoutePlanningService;

class RouteController extends CrudController
{
    protected $repository_class = RouteSegmentRepository::class;
    protected $model_class = RouteSegment::class;
    protected $view_path = 'routes';
    protected $entity_name = 'route_segment';

    public function map()
    {
        return $this->index();
    }

    public function find()
    {
        return $this->route_response('find_route', 'Route not found.');
    }

    public function accessible()
    {
        return $this->route_response('find_accessible_route', 'Accessible route not found.');
    }

    public function adjacent($room_id = null)
    {
        return $this->guarded_response(null, function () use ($room_id) {
            $data = $this->payload();
            $rooms = (new RoutePlanningService(null, $this->repository))->get_adjacent_rooms(
                is_numeric($room_id) ? (int) $room_id : ($data['room_id'] ?? null),
                filter_var($data['accessible_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
                $data['start_time'] ?? null,
                $data['end_time'] ?? null
            );

            return $rooms === null ? $this->error('Invalid room.', 422) : $this->success(['rooms' => $rooms]);
        });
    }

    private function route_response($method, $not_found_message)
    {
        return $this->guarded_response(null, function () use ($method, $not_found_message) {
            $data = $this->payload();
            $service = new RoutePlanningService(null, $this->repository);
            $route = $service->$method(
                $data['from_room_id'] ?? null,
                $data['to_room_id'] ?? null,
                $data['start_time'] ?? null,
                $data['end_time'] ?? null
            );

            return $route === null
                ? $this->error($not_found_message, 404)
                : $this->success(['route' => $route, 'distance' => $service->get_route_distance($route)]);
        });
    }
}
