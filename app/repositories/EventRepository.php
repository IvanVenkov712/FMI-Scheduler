<?php
namespace App\Repositories;
use App\Core\Repository;
use App\models\User;
use App\models\Event;
use App\models\Room;
use App\models\ResourceRequirement;

class EventRepository extends Repository {
    public function __construct($database) {
        parent::__construct($database, Event::class, 'events');
    }
    public function get_requestor($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT u.* FROM `users` u
            INNER JOIN `events` e ON e.requestor_id = u.id
            WHERE e.id = ? LIMIT 1"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : new User($row);
    }

    public function get_room($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT r.* FROM `rooms` r
            INNER JOIN `events` e ON e.room_id = r.id
            WHERE e.id = ? LIMIT 1"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : new Room($row);
    }

    public function get_resource_requirements($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `resource_requirements` WHERE event_id = ?"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map(
            fn ($row) => new ResourceRequirement($row),
            $statement->fetchAll()
        );
    }

    public function get_all_events($start_time, $end_time) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `{$this->table}`
            WHERE start_time <= ? AND end_time >= ?
            ORDER BY `start_time` ASC"
        );
        $statement->bindValue(1, $end_time);
        $statement->bindValue(2, $start_time);
        $statement->execute();

        return array_map(
            fn ($row) => $this->map_row_to_model($row),
            $statement->fetchAll()
        );
    }

    public function get_online_access($id) {
        
    }


}
