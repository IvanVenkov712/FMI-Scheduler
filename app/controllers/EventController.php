<?php
namespace App\Controllers;

use App\Models\Event;
use App\Repositories\EventRepository;
use App\Services\EventApprovalService;
use App\Services\EventRequestService;
use App\Services\OnlineAccessService;

class EventController extends CrudController
{
    private const APPROVAL_ROLES = ['admin', 'schedule_manager'];

    private $request_service;
    private $approval_service;
    private $online_access_service;

    protected $repository_class = EventRepository::class;
    protected $model_class = Event::class;
    protected $view_path = 'events';
    protected $entity_name = 'event';
    protected $write_roles = ['admin', 'teacher', 'schedule_manager'];
    protected $validation_rules = [
        'requestor_id' => ['required', 'integer', 'min:1'],
        'name' => ['required', 'max_length:150'],
        'type' => ['in:lecture,exam,practice,consultation'],
        'start_time' => ['required', 'date'],
        'end_time' => ['required', 'date', 'after:start_time'],
        'delivery_mode' => ['in:physical,online,hybrid'],
        'student_count' => ['integer', 'min:0'],
        'status' => ['in:pending,approved,rejected,cancelled'],
    ];

    public function __construct(
        $request = [],
        $view = null,
        $session = null,
        $repository = null,
        $request_service = null,
        $approval_service = null,
        $online_access_service = null,
    ) {
        parent::__construct($request, $view, $session, $repository);
        $this->request_service = $request_service ?? new EventRequestService($this->repository);
        $this->approval_service = $approval_service ?? new EventApprovalService($this->repository);
        $this->online_access_service = $online_access_service ?? new OnlineAccessService($this->repository);
    }

    public function show($id = null)
    {
        return $this->guarded_response(null, function () use ($id) {
            $id = $this->resolve_id($id);
            $event = $this->repository->find_by_id($id);
            if ($event === null) {
                return $this->not_found('Event not found.');
            }

            return $this->respond('events/details', $this->details_data($id, $event));
        });
    }

    public function submit_request()
    {
        return $this->guarded_response($this->write_roles, function () {
            $data = $this->payload();
            $event = $this->event_from_payload($data);
            $created = $this->request_service->submit_event_request(
                $event,
                $data['resource_requirements'] ?? [],
                $data['online_access'] ?? null
            );

            return $created === null
                ? $this->error('Event request could not be submitted.', 422, $this->request_service->errors())
                : $this->success(['event' => $created], 201);
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
            $this->request_service->attach_resource_requirements(
                $this->resolve_id($id),
                $this->payload()['resource_requirements'] ?? []
            )
        ));
    }

    public function provision_online_access($id = null)
    {
        return $this->guarded_response($this->write_roles, function () use ($id) {
            $online_access_data = $this->online_access_payload($this->payload());
            $online_access = $this->online_access_service->provision_online_access(
                $this->resolve_id($id),
                $online_access_data['platform'],
                $online_access_data['meeting_link'],
                $online_access_data['access_key'],
                $online_access_data['host_notes']
            );

            return $this->result($online_access, 'online_access', 'Online access could not be provisioned.');
        });
    }

    private function approval_response($id, $method, $message)
    {
        return $this->guarded_response(self::APPROVAL_ROLES, function () use ($id, $method, $message) {
            $event = $this->approval_service->$method($this->resolve_id($id));
            return $this->result($event, 'event', $message);
        });
    }

    private function details_data($event_id, $event)
    {
        return [
            'event' => $event,
            'requestor' => $this->repository->get_requestor($event_id),
            'room' => $this->repository->get_room($event_id),
            'resource_requirements' => $this->repository->get_resource_requirements($event_id),
            'online_access' => $this->repository->get_online_access($event_id),
        ];
    }

    private function event_from_payload($data)
    {
        $event = new Event($data);
        $event->requestor_id = $event->requestor_id ?: $this->request_user_id();

        return $event;
    }

    private function online_access_payload($data)
    {
        return [
            'platform' => $data['platform'] ?? '',
            'meeting_link' => $data['meeting_link'] ?? ($data['link'] ?? ''),
            'access_key' => $data['access_key'] ?? null,
            'host_notes' => $data['host_notes'] ?? '',
        ];
    }

}
