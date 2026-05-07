<?php
namespace App\Controllers;

use App\Models\Event;
use App\Repositories\EventRepository;
use App\Services\EventApprovalService;
use App\Services\EventRequestService;
use App\Services\OnlineAccessService;

class EventController extends CrudController
{
    protected $repository_class = EventRepository::class;
    protected $model_class = Event::class;
    protected $view_path = 'events';
    protected $entity_name = 'event';
    protected $write_roles = ['admin', 'teacher', 'schedule_manager'];

    public function show($id = null)
    {
        return $this->guarded_response(null, function () use ($id) {
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
        });
    }

    public function submit_request()
    {
        return $this->guarded_response($this->write_roles, function () {
            $data = $this->payload();
            $event = new Event($data);
            $event->requestor_id = $event->requestor_id ?: $this->request_user_id();
            $created = (new EventRequestService($this->repository))->submit_event_request(
                $event,
                $data['resource_requirements'] ?? [],
                $data['online_access'] ?? null
            );

            return $this->result($created, 'event', 'Event request could not be submitted.', 201);
        });
    }

    public function approve($id = null)
    {
        return $this->approval_response($id, 'approve_event_request', 'Event could not be approved.');
    }

    public function reject($id = null)
    {
        return $this->approval_response($id, 'reject_event_request', 'Event could not be rejected.');
    }

    public function reschedule($id = null)
    {
        return $this->approval_response($id, 'reschedule_approved_event', 'Event could not be rescheduled.');
    }

    public function attach_requirements($id = null)
    {
        return $this->guarded_response($this->write_roles, fn () => $this->ok(
            (new EventRequestService($this->repository))->attach_resource_requirements(
                $this->resolve_id($id),
                $this->payload()['resource_requirements'] ?? []
            )
        ));
    }

    public function provision_online_access($id = null)
    {
        return $this->guarded_response($this->write_roles, function () use ($id) {
            $data = $this->payload();
            $online_access = (new OnlineAccessService($this->repository))->provision_online_access(
                $this->resolve_id($id),
                $data['platform'] ?? '',
                $data['meeting_link'] ?? ($data['link'] ?? ''),
                $data['access_key'] ?? null,
                $data['host_notes'] ?? ''
            );

            return $this->result($online_access, 'online_access', 'Online access could not be provisioned.');
        });
    }

    private function approval_response($id, $method, $message)
    {
        return $this->guarded_response(['admin', 'schedule_manager'], function () use ($id, $method, $message) {
            $event = (new EventApprovalService($this->repository))->$method($this->resolve_id($id));
            return $this->result($event, 'event', $message);
        });
    }
}
