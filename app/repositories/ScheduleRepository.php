<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Schedule;
use App\Models\Event;

class ScheduleRepository extends Repository 
{
    protected $model_class = Schedule::class;
    protected $table = 'schedules';

    public function get_events($id)
    {
        return $this->rows(
            "SELECT e.* FROM `events` e
            INNER JOIN `schedule_events` se ON se.event_id = e.id
            WHERE se.schedule_id = ?
            ORDER BY e.`start_time` ASC",
            [$id],
            Event::class
        );
    }

    public function add_event_in_schedule($schedule_id, $event_id)
    {
        $statement = $this->run(
            "INSERT INTO `schedule_events` (`schedule_id`, `event_id`)
            VALUES (?, ?)",
            [$schedule_id, $event_id]
        );

        return $statement->rowCount() > 0;
    }

    public function has_event_in_schedule($schedule_id, $event_id)
    {
        return $this->value(
            "SELECT 1 FROM `schedule_events`
            WHERE schedule_id = ? AND event_id = ?
            LIMIT 1",
            [$schedule_id, $event_id]
        ) !== null;
    }

    public function remove_event_from_schedule($schedule_id, $event_id)
    {
        $statement = $this->run(
            "DELETE FROM `schedule_events`
            WHERE schedule_id = ? AND event_id = ?",
            [$schedule_id, $event_id]
        );

        return $statement->rowCount() > 0;
    }
}
