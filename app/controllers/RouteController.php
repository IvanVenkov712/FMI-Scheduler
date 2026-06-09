<?php
namespace App\Controllers;

use App\Models\RouteSegment;
use App\Repositories\RoomRepository;
use App\Repositories\RouteSegmentRepository;
use App\Services\RoutePlanningService;

class RouteController extends CrudController
{
    private $route_planning_service;

    protected $repository_class = RouteSegmentRepository::class;
    protected $model_class = RouteSegment::class;
    protected $view_path = 'routes';
    protected $entity_name = 'route_segment';
    protected $validation_rules = [
        'from_room_id' => ['required', 'integer', 'min:1'],
        'to_room_id' => ['required', 'integer', 'min:1', 'different:from_room_id'],
        'distance' => ['numeric', 'min:0'],
        'corridor_capacity' => ['integer', 'min:0'],
    ];
    protected $field_labels = [
        'from_room_id' => 'From room',
        'to_room_id' => 'To room',
    ];

    public function __construct(
        $request = [],
        $view = null,
        $session = null,
        $repository = null,
        $route_planning_service = null,
    ) {
        parent::__construct($request, $view, $session, $repository);
        $this->route_planning_service = $route_planning_service
            ?? new RoutePlanningService(null, $this->repository);
    }

    public function map()
    {
        return $this->index();
    }

    protected function index_view_data()
    {
        return ['rooms' => (new RoomRepository())->find_all()];
    }

    public function find()
    {
        $data = $this->payload();
        $data['start_time'] = trim((string) ($data['start_time'] ?? '')) ?: null;
        $data['end_time'] = trim((string) ($data['end_time'] ?? '')) ?: null;
        $accessible_only = filter_var($data['accessible_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $this->route_response(
            $accessible_only ? 'find_accessible_route' : 'find_route',
            $accessible_only ? 'Accessible route not found.' : 'Route not found.',
            $data
        );
    }

    public function accessible()
    {
        return $this->route_response('find_accessible_route', 'Accessible route not found.');
    }

    public function adjacent($room_id = null)
    {
        return $this->guarded_response(null, function () use ($room_id) {
            $data = $this->payload();
            $rooms = $this->route_planning_service->get_adjacent_rooms(
                is_numeric($room_id) ? (int) $room_id : ($data['room_id'] ?? null),
                filter_var($data['accessible_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
                $data['start_time'] ?? null,
                $data['end_time'] ?? null
            );

            return $rooms === null ? $this->error('Invalid room.', 422) : $this->success(['rooms' => $rooms]);
        });
    }

    private function route_response($method, $not_found_message, $data = null)
    {
        return $this->guarded_response(null, function () use ($method, $not_found_message, $data) {
            $data = $data ?? $this->payload();
            $route = $this->route_planning_service->$method(
                $data['from_room_id'] ?? null,
                $data['to_room_id'] ?? null,
                $data['start_time'] ?? null,
                $data['end_time'] ?? null
            );

            if (!$this->wants_json()) {
                return $this->respond($this->view_path . '/index', [
                    'route_segments' => $this->repository->find_all(),
                    'route_result' => $route,
                    'route_distance' => $route === null
                        ? null
                        : $this->route_planning_service->get_route_distance($route),
                    'route_search' => $data,
                    'route_search_error' => $route === null ? $not_found_message : null,
                ] + $this->index_view_data());
            }

            return $route === null
                ? $this->error($not_found_message, 404)
                : $this->success([
                    'route' => $route,
                    'distance' => $this->route_planning_service->get_route_distance($route),
                ]);
        });
    }
}
