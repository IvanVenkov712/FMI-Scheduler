<?php
namespace App\Controllers;

use App\Models\Room;
use App\Repositories\RoomRepository;
use App\Services\RoomAvailabilityService;
use App\Services\RoomResourceService;

class RoomController extends CrudController
{
    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->repository = $repository ?? new RoomRepository();
        $this->model_class = Room::class;
        $this->view_path = 'rooms';
        $this->entity_name = 'room';
    }

    public function availability($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $data = $this->payload();
        $service = new RoomAvailabilityService($this->repository);
        $bookable = $service->is_room_bookable(
            $this->resolve_id($id),
            $data['start_time'] ?? '',
            $data['end_time'] ?? '',
            $data['student_count'] ?? 0,
            $data['resource_requirements'] ?? []
        );

        return $this->success([
            'bookable' => $bookable,
            'conflicts' => $service->find_conflicting_events(
                $this->resolve_id($id),
                $data['start_time'] ?? '',
                $data['end_time'] ?? ''
            ),
        ]);
    }

    public function resources($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        return $this->success([
            'resources' => $this->repository->get_available_resources($this->resolve_id($id)),
        ]);
    }

    public function sync_resource($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $data = $this->payload();
        $ok = (new RoomResourceService($this->repository))->sync_room_resource(
            $this->resolve_id($id),
            $data['resource_id'] ?? null,
            $data['quantity'] ?? 0
        );

        return $this->json(['success' => $ok], $ok ? 200 : 422);
    }

    public function events($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        return $this->success([
            'events' => $this->repository->get_events($this->resolve_id($id)),
        ]);
    }
}
