<?php
namespace App\Controllers;

use App\Models\Event;
use App\Repositories\EventRepository;
use App\Services\EventApprovalService;
use App\Services\EventRequestService;
use App\Services\OnlineAccessService;

class EventController extends CrudController
{
    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->repository = $repository ?? new EventRepository();
        $this->model_class = Event::class;
        $this->view_path = 'events';
        $this->entity_name = 'event';
        $this->write_roles = ['admin', 'teacher', 'schedule_manager'];
    }

    public function show($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        $event = $this->repository->find_by_id($id);
        if ($event === null) {
            return $this->not_found('Event not found.');
        }

        return $this->respond('events/details', [
            'event' => $event,
            'requestor' => $this->repository->get_requestor($id),
            'room' => $this->repository->get_room($id),
            'resource_requirements' => $this->repository->get_resource_requirements($id),
            'online_access' => $this->repository->get_online_access($id),
        ]);
    }

    public function submit_request()
    {
        if ($guard = $this->guarded(['admin', 'teacher', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $event = new Event($data);
        $event->requestor_id = $event->requestor_id ?: $this->request_user_id();

        $created = (new EventRequestService($this->repository))->submit_event_request(
            $event,
            $data['resource_requirements'] ?? [],
            $data['online_access'] ?? null
        );

        return $created === null
            ? $this->error('Event request could not be submitted.', 422)
            : $this->success(['event' => $created], 201);
    }

    public function approve($id = null)
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $event = (new EventApprovalService($this->repository))->approve_event_request($this->resolve_id($id));

        return $event === null ? $this->error('Event could not be approved.', 422) : $this->success(['event' => $event]);
    }

    public function reject($id = null)
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $event = (new EventApprovalService($this->repository))->reject_event_request($this->resolve_id($id));

        return $event === null ? $this->error('Event could not be rejected.', 422) : $this->success(['event' => $event]);
    }

    public function reschedule($id = null)
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $event = (new EventApprovalService($this->repository))->reschedule_approved_event($this->resolve_id($id));

        return $event === null ? $this->error('Event could not be rescheduled.', 422) : $this->success(['event' => $event]);
    }

    public function attach_requirements($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $ok = (new EventRequestService($this->repository))->attach_resource_requirements(
            $this->resolve_id($id),
            $this->payload()['resource_requirements'] ?? []
        );

        return $this->json(['success' => $ok], $ok ? 200 : 422);
    }

    public function provision_online_access($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $data = $this->payload();
        $online_access = (new OnlineAccessService($this->repository))->provision_online_access(
            $this->resolve_id($id),
            $data['platform'] ?? '',
            $data['meeting_link'] ?? ($data['link'] ?? ''),
            $data['access_key'] ?? null,
            $data['host_notes'] ?? ''
        );

        return $online_access === null
            ? $this->error('Online access could not be provisioned.', 422)
            : $this->success(['online_access' => $online_access]);
    }
}
