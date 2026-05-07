<?php
namespace App\Services;

use App\Helpers\TimeHelper;
use App\Repositories\EventRepository;
use App\Repositories\ScheduleRepository;

class SchedulePublishingService extends BaseService
{
    private $schedule_repository;
    private $event_repository;

    public function __construct($schedule_repository = null, $event_repository = null)
    {
        $this->schedule_repository = $this->dependency($schedule_repository, ScheduleRepository::class);
        $this->event_repository = $this->dependency($event_repository, EventRepository::class);
    }

    public function publish_event_to_schedule($event_id, $schedule_id)
    {
        $event = $this->event_repository->find_by_id($event_id);
        $schedule = $this->schedule_repository->find_by_id($schedule_id);

        if (!$this->can_publish_event($event, $schedule)) {
            return null;
        }

        if ($this->schedule_repository->has_event_in_schedule($schedule_id, $event_id)) {
            return $event;
        }

        if (!$this->schedule_repository->add_event_in_schedule($schedule_id, $event_id)) {
            return null;
        }

        return $event;
    }

    public function remove_event_from_schedule($event_id, $schedule_id) 
    {
        if (!$this->schedule_repository->exists($schedule_id) || !$this->event_repository->exists($event_id)) {
            return false;
        }

        if (!$this->schedule_repository->has_event_in_schedule($schedule_id, $event_id)) {
            return false;
        }

        return $this->schedule_repository->remove_event_from_schedule($schedule_id, $event_id);
    }

    public function publish_batch($events, $schedule_id)
    {
        if (!is_array($events)) {
            return [];
        }

        $published_events = [];
        foreach ($events as $event) {
            $event_id = $this->persist_batch_event($event);
            if ($event_id === null) {
                continue;
            }

            $published_event = $this->publish_event_to_schedule($event_id, $schedule_id);

            if ($published_event !== null) {
                $published_events[] = $published_event;
            }
        }

        return $published_events;
    }

    private function persist_batch_event($event)
    {
        if (!is_object($event)) {
            return $event;
        }

        if (property_exists($event, 'id') && $this->event_repository->exists($event->id)) {
            return $event->id;
        }

        return (int) $this->event_repository->insert($event);
    }

    private function can_publish_event($event, $schedule)
    {
        if ($event === null || $schedule === null) {
            return false;
        }

        if (!$event->is_approved()) {
            return false;
        }

        if (!TimeHelper::is_valid_interval($event->start_time, $event->end_time)) {
            return false;
        }

        return $this->event_fits_schedule_dates($event, $schedule);
    }

    private function event_fits_schedule_dates($event, $schedule)
    {
        $event_start = strtotime((string) $event->start_time);
        $event_end = strtotime((string) $event->end_time);
        $schedule_start = strtotime((string) $schedule->start_date . ' 00:00:00');
        $schedule_end = strtotime((string) $schedule->end_date . ' 23:59:59');

        return $schedule_start !== false
            && $schedule_end !== false
            && $schedule_start <= $schedule_end
            && $event_start >= $schedule_start
            && $event_end <= $schedule_end;
    }
}
