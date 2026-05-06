<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Schedule;
use App\Models\Event;

class ScheduleRepository extends Repository 
{
    public function __construct($database = null) 
    {
        parent::__construct($database, Schedule::class, 'schedules');
    }

    public function get_events($id)
    {
        $statement = $this->database->connection()->prepare(
            "SELECT e.* FROM `events` e
            INNER JOIN `schedule_events` se ON se.event_id = e.id
            WHERE se.schedule_id = ?
            ORDER BY e.`start_time` ASC"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map(
            fn ($row) => new Event($row),
            $statement->fetchAll()
        );
    }

    public function add_event_in_schedule($schedule_id, $event_id)
    {
        $statement = $this->database->connection()->prepare(
            "INSERT INTO `schedule_events` (`schedule_id`, `event_id`)
            VALUES (?, ?)"
        );
        $statement->bindValue(1, $schedule_id);
        $statement->bindValue(2, $event_id);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    public function has_event_in_schedule($schedule_id, $event_id)
    {
        $statement = $this->database->connection()->prepare(
            "SELECT 1 FROM `schedule_events`
            WHERE schedule_id = ? AND event_id = ?
            LIMIT 1"
        );
        $statement->bindValue(1, $schedule_id);
        $statement->bindValue(2, $event_id);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    public function remove_event_from_schedule($schedule_id, $event_id)
    {
        $statement = $this->database->connection()->prepare(
            "DELETE FROM `schedule_events`
            WHERE schedule_id = ? AND event_id = ?"
        );
        $statement->bindValue(1, $schedule_id);
        $statement->bindValue(2, $event_id);
        $statement->execute();

        return $statement->rowCount() > 0;
    }
}
