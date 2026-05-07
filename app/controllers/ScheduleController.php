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
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        $schedule = $this->repository->find_by_id($id);
        if ($schedule === null) {
            return $this->not_found('Schedule not found.');
        }

        return $this->respond('schedules/details', [
            'schedule' => $schedule,
            'events' => $this->repository->get_events($id),
        ]);
    }

    public function calendar($id = null)
    {
        return $this->show($id);
    }

    public function publish_event($id = null)
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $schedule_id = $this->resolve_id($id);
        $event_id = $this->int_param('event_id', $this->payload()['event_id'] ?? null);
        $published = (new SchedulePublishingService($this->repository))->publish_event_to_schedule($event_id, $schedule_id);

        return $this->ok($published);
    }

    public function remove_event($id = null)
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $schedule_id = $this->resolve_id($id);
        $event_id = $this->int_param('event_id', $this->payload()['event_id'] ?? null);
        $removed = (new SchedulePublishingService($this->repository))->remove_event_from_schedule($event_id, $schedule_id);

        return $this->ok($removed);
    }
}
