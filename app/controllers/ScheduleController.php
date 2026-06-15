<?php
namespace App\Controllers;

use App\Models\Schedule;
use App\Repositories\ScheduleRepository;
use App\Services\SchedulePublishingService;

class ScheduleController extends CrudController
{
    private $publishing_service;

    protected $repository_class = ScheduleRepository::class;
    protected $model_class = Schedule::class;
    protected $view_path = 'schedules';
    protected $entity_name = 'schedule';
    protected $validation_rules = [
        'name' => ['required', 'max_length:150'],
        'start_date' => ['required', 'date'],
        'end_date' => ['required', 'date', 'after:start_date'],
    ];

    public function __construct(
        $request = [],
        $view = null,
        $session = null,
        $repository = null,
        $publishing_service = null,
    ) {
        parent::__construct($request, $view, $session, $repository);
        $this->publishing_service = $publishing_service ?? new SchedulePublishingService($this->repository);
    }

    public function show($id = null)
    {
        return $this->guarded_response(null, function () use ($id) {
            $id = $this->resolve_id($id);
            $schedule = $this->repository->find_by_id($id);
            if ($schedule === null) {
                return $this->not_found('Schedule not found.');
            }

            return $this->respond('schedules/details', [
                'schedule' => $schedule,
                'events' => $this->repository->get_events($id),
                'publishable_events' => $this->repository->get_publishable_events($id),
            ]);
        });
    }

    public function calendar($id = null)
    {
        return $this->show($id);
    }

    public function publish_event($id = null)
    {
        return $this->schedule_event_response(
            $id,
            'publish_event_to_schedule',
            'Event could not be added. It must be valid, bookable, and within the schedule period.'
        );
    }

    public function remove_event($id = null)
    {
        return $this->schedule_event_response($id, 'remove_event_from_schedule', 'Event could not be removed.');
    }

    private function schedule_event_response($id, $method, $error_message)
    {
        return $this->guarded_response(['admin', 'schedule_manager'], function () use ($id, $method, $error_message) {
            $schedule_id = $this->resolve_id($id);
            $event_id = $this->int_param('event_id', $this->payload()['event_id'] ?? null);
            $result = $this->publishing_service->$method($event_id, $schedule_id);

            return $result
                ? $this->success(['schedule_id' => $schedule_id, 'event_id' => $event_id])
                : $this->error($error_message, 422);
        });
    }
}
