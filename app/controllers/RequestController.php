<?php
namespace App\Controllers;

use App\Models\Event;
use App\Repositories\EventRepository;
use App\Services\EventRequestService;
use App\Services\UserEventAccessService;

class RequestController extends BaseController
{
    private $event_repository;
    private $request_service;

    public function __construct($request = [], $view = null, $session = null, $event_repository = null, $request_service = null)
    {
        parent::__construct($request, $view, $session);
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->request_service = $request_service ?? new EventRequestService($this->event_repository);
    }

    public function index()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $access = new UserEventAccessService(null, $this->event_repository);

        return $this->respond('requests/index', [
            'events' => $access->list_manageable_events($this->request_user_id()),
            'approvable_events' => $access->list_approvable_events($this->request_user_id()),
        ]);
    }

    public function create()
    {
        if ($guard = $this->guarded(['admin', 'teacher', 'schedule_manager'])) {
            return $guard;
        }

        return $this->respond('requests/create', []);
    }

    public function store()
    {
        if ($guard = $this->guarded(['admin', 'teacher', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $event = new Event($data);
        $event->requestor_id = $event->requestor_id ?: $this->request_user_id();

        $created = $this->request_service->submit_event_request(
            $event,
            $data['resource_requirements'] ?? [],
            $data['online_access'] ?? null
        );

        return $created === null ? $this->error('Request could not be created.', 422) : $this->success(['event' => $created], 201);
    }

    public function update($id = null)
    {
        if ($guard = $this->guarded(['admin', 'teacher', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $event = new Event($data);
        $event->requestor_id = $event->requestor_id ?: $this->request_user_id();

        $updated = $this->request_service->edit_pending_request(
            is_numeric($id) ? (int) $id : $this->int_param('id', 0),
            $event,
            $data['resource_requirements'] ?? [],
            $data['online_access'] ?? null
        );

        return $updated === null ? $this->error('Request could not be updated.', 422) : $this->success(['event' => $updated]);
    }

    public function review($id = null)
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $id = is_numeric($id) ? (int) $id : $this->int_param('id', 0);
        $event = $this->event_repository->find_by_id($id);
        if ($event === null) {
            return $this->not_found('Request not found.');
        }

        return $this->respond('requests/review', [
            'event' => $event,
            'resource_requirements' => $this->event_repository->get_resource_requirements($id),
            'online_access' => $this->event_repository->get_online_access($id),
        ]);
    }
}
