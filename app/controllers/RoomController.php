<?php
namespace App\Controllers;

use App\Models\Room;
use App\Repositories\RoomRepository;
use App\Services\RoomAvailabilityService;
use App\Services\RoomResourceService;

class RoomController extends CrudController
{
    protected $repository_class = RoomRepository::class;
    protected $model_class = Room::class;
    protected $view_path = 'rooms';
    protected $entity_name = 'room';

    public function availability($id = null)
    {
        return $this->guarded_response(null, function () use ($id) {
            $data = $this->payload();
            $service = new RoomAvailabilityService($this->repository);

            return $this->success([
                'bookable' => $service->is_room_bookable(
                    $this->resolve_id($id),
                    $data['start_time'] ?? '',
                    $data['end_time'] ?? '',
                    $data['student_count'] ?? 0,
                    $data['resource_requirements'] ?? []
                ),
                'conflicts' => $service->find_conflicting_events(
                    $this->resolve_id($id),
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

            return $this->ok((new RoomResourceService($this->repository))->sync_room_resource(
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
