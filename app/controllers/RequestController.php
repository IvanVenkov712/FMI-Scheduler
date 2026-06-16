<?php
namespace App\Controllers;

use App\Models\Event;
use App\Repositories\BuildingRepository;
use App\Repositories\EventRepository;
use App\Repositories\FloorRepository;
use App\Repositories\RoomRepository;
use App\Services\EventRequestService;
use App\Services\UserEventAccessService;

class RequestController extends BaseController
{
    private $event_repository;
    private $request_service;

    private $access;

    public function __construct($request = [], $view = null, $session = null, $event_repository = null, $request_service = null, $user_event_access_service = null)
    {
        parent::__construct($request, $view, $session);
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->request_service = $request_service ?? new EventRequestService($this->event_repository);
        $this->access = $user_event_access_service ?? new UserEventAccessService(null, $this->event_repository);
    }

    public function index()
    {
        return $this->guarded_response(null, function () {
            $filters = $this->request_filters();
            $events = $this->access->list_manageable_events($this->request_user_id());
            $approvable_events = $this->access->list_approvable_events($this->request_user_id());

            if ($this->has_active_filters($filters)) {
                $matching_ids = array_flip(array_map(
                    fn ($event) => (int) $event->id,
                    $this->event_repository->find_filtered($filters)
                ));
                $events = $this->only_matching_events($events, $matching_ids);
                $approvable_events = $this->only_matching_events($approvable_events, $matching_ids);
            }

            return $this->respond('requests/index', [
                'events' => $events,
                'approvable_events' => $approvable_events,
                'filters' => $filters,
                'buildings' => (new BuildingRepository())->find_all(),
                'floors' => (new FloorRepository())->find_all(),
                'rooms' => (new RoomRepository())->find_all(),
            ]);
        });
    }

    public function create()
    {
        return $this->guarded_response($this->request_roles(), fn () => $this->respond('requests/create', [
            'rooms' => (new RoomRepository())->find_all(),
        ]));
    }

    public function store()
    {
        return $this->request_mutation_response(
            fn ($id, $event, $data) => $this->request_service->submit_event_request(
                $event,
                $data['resource_requirements'] ?? [],
                $data['online_access'] ?? null
            ),
            'Request could not be created.',
            201
        );
    }

    public function update($id = null)
    {
        return $this->request_mutation_response(
            fn ($resolved_id, $event, $data) => $this->request_service->edit_pending_request(
                $resolved_id,
                $event,
                $data['resource_requirements'] ?? [],
                $data['online_access'] ?? null
            ),
            'Request could not be updated.',
            200,
            $id
        );
    }

    public function review($id = null)
    {
        return $this->guarded_response(['admin', 'schedule_manager'], function () use ($id) {
            $id = $this->resolve_id($id);
            $event = $this->event_repository->find_by_id($id);
            if ($event === null) {
                return $this->not_found('Request not found.');
            }

            return $this->respond('requests/review', [
                'event' => $event,
                'resource_requirements' => $this->event_repository->get_resource_requirements($id),
                'online_access' => $this->event_repository->get_online_access($id),
            ]);
        });
    }

    private function request_mutation_response($callback, $message, $status = 200, $id = null)
    {
        return $this->guarded_response($this->request_roles(), function () use ($callback, $message, $status, $id) {
            $data = $this->payload();
            $event = new Event($data);
            $event->requestor_id = $event->requestor_id ?: $this->request_user_id();
            $result = $callback($this->resolve_id($id), $event, $data);

            return $result === null
                ? $this->error($message, 422, $this->request_service->errors())
                : $this->success(['event' => $result], $status);
        });
    }

    private function request_roles()
    {
        return ['admin', 'teacher', 'schedule_manager'];
    }

    private function request_filters()
    {
        $filters = [];
        foreach ([
            'q',
            'name',
            'building_id',
            'floor_id',
            'room_id',
            'delivery_mode',
            'status',
            'date_from',
            'date_to',
        ] as $key) {
            $value = $this->input($key, '');
            $filters[$key] = is_array($value) ? '' : trim((string) $value);
        }

        $filters['date_from'] = $this->date_filter($filters['date_from']);
        $filters['date_to'] = $this->date_filter($filters['date_to']);

        return $filters;
    }

    private function has_active_filters($filters)
    {
        foreach ($filters as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function only_matching_events($events, $matching_ids)
    {
        return array_values(array_filter($events, fn ($event) => isset($matching_ids[(int) $event->id])));
    }

    private function date_filter($value)
    {
        $value = trim((string) $value);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }
}
