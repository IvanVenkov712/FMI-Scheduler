<?php
namespace App\Controllers;

use App\Models\RouteSegment;
use App\Repositories\RouteSegmentRepository;
use App\Services\RoutePlanningService;

class RouteController extends CrudController
{
    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->repository = $repository ?? new RouteSegmentRepository();
        $this->model_class = RouteSegment::class;
        $this->view_path = 'routes';
        $this->entity_name = 'route_segment';
    }

    protected function collection_key()
    {
        return 'route_segments';
    }

    public function map()
    {
        return $this->index();
    }

    public function find()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $data = $this->payload();
        $service = new RoutePlanningService(null, $this->repository);
        $route = $service->find_route(
            $data['from_room_id'] ?? null,
            $data['to_room_id'] ?? null,
            $data['start_time'] ?? null,
            $data['end_time'] ?? null
        );

        return $route === null
            ? $this->error('Route not found.', 404)
            : $this->success(['route' => $route, 'distance' => $service->get_route_distance($route)]);
    }

    public function accessible()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $data = $this->payload();
        $service = new RoutePlanningService(null, $this->repository);
        $route = $service->find_accessible_route(
            $data['from_room_id'] ?? null,
            $data['to_room_id'] ?? null,
            $data['start_time'] ?? null,
            $data['end_time'] ?? null
        );

        return $route === null
            ? $this->error('Accessible route not found.', 404)
            : $this->success(['route' => $route, 'distance' => $service->get_route_distance($route)]);
    }

    public function adjacent($room_id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $data = $this->payload();
        $service = new RoutePlanningService(null, $this->repository);
        $rooms = $service->get_adjacent_rooms(
            is_numeric($room_id) ? (int) $room_id : ($data['room_id'] ?? null),
            filter_var($data['accessible_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            $data['start_time'] ?? null,
            $data['end_time'] ?? null
        );

        return $rooms === null ? $this->error('Invalid room.', 422) : $this->success(['rooms' => $rooms]);
    }
}
