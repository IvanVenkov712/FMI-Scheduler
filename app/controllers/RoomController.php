<?php
namespace App\Controllers;

use App\Models\Room;
use App\Repositories\RoomRepository;
use App\Services\RoomAvailabilityService;
use App\Services\RoomResourceService;

class RoomController extends CrudController
{
    private $availability_service;
    private $resource_service;

    protected $repository_class = RoomRepository::class;
    protected $model_class = Room::class;
    protected $view_path = 'rooms';
    protected $entity_name = 'room';
    protected $validation_rules = [
        'floor_id' => ['required', 'integer', 'min:1'],
        'name' => ['required', 'max_length:100'],
        'position_code' => ['max_length:50'],
        'room_type' => ['in:physical,online,hybrid'],
        'capacity' => ['integer', 'min:0'],
        'room_size' => ['in:small,medium,large'],
    ];
    protected $field_labels = ['floor_id' => 'Floor'];

    public function __construct(
        $request = [],
        $view = null,
        $session = null,
        $repository = null,
        $availability_service = null,
        $resource_service = null,
    ) {
        parent::__construct($request, $view, $session, $repository);
        $this->availability_service = $availability_service ?? new RoomAvailabilityService($this->repository);
        $this->resource_service = $resource_service ?? new RoomResourceService($this->repository);
    }

    public function availability($id = null)
    {
        return $this->guarded_response(null, function () use ($id) {
            $data = $this->payload();
            $room_id = $this->resolve_id($id);

            return $this->success([
                'bookable' => $this->availability_service->is_room_bookable(
                    $room_id,
                    $data['start_time'] ?? '',
                    $data['end_time'] ?? '',
                    $data['student_count'] ?? 0,
                    $data['resource_requirements'] ?? []
                ),
                'conflicts' => $this->availability_service->find_conflicting_events(
                    $room_id,
                    $data['start_time'] ?? '',
                    $data['end_time'] ?? ''
                ),
            ]);
        });
    }

    public function resources($id = null)
    {
        return $this->guarded_response(null, fn () => $this->success([
            'resources' => $this->repository->get_available_resources($this->resolve_id($id)),
        ]));
    }

    public function sync_resource($id = null)
    {
        return $this->guarded_response($this->write_roles, function () use ($id) {
            $data = $this->payload();

            return $this->ok($this->resource_service->sync_room_resource(
                $this->resolve_id($id),
                $data['resource_id'] ?? null,
                $data['quantity'] ?? 0
            ));
        });
    }

    public function events($id = null)
    {
        return $this->guarded_response(null, fn () => $this->success([
            'events' => $this->repository->get_events($this->resolve_id($id)),
        ]));
    }
}
