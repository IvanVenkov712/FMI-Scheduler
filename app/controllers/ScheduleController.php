<?php
namespace App\Controllers;

use App\Models\Schedule;
use App\Repositories\ScheduleRepository;
use App\Services\SchedulePublishingService;

class ScheduleController extends CrudController
{
    protected $repository_class = ScheduleRepository::class;
    protected $model_class = Schedule::class;
    protected $view_path = 'schedules';
    protected $entity_name = 'schedule';

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
            ]);
        });
    }

    public function calendar($id = null)
    {
        return $this->show($id);
    }

    public function publish_event($id = null)
    {
        return $this->schedule_event_response($id, 'publish_event_to_schedule');
    }

    public function remove_event($id = null)
    {
        return $this->schedule_event_response($id, 'remove_event_from_schedule');
    }

    private function schedule_event_response($id, $method)
    {
        return $this->guarded_response(['admin', 'schedule_manager'], function () use ($id, $method) {
            $schedule_id = $this->resolve_id($id);
            $event_id = $this->int_param('event_id', $this->payload()['event_id'] ?? null);

            return $this->ok((new SchedulePublishingService($this->repository))->$method($event_id, $schedule_id));
        });
    }
}
