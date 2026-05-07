<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\RouteSegment;
use App\Models\Room;

class RouteSegmentRepository extends Repository {
    public function __construct($database = null) {
        parent::__construct($database, RouteSegment::class, 'route_segments');
    }

    public function get_from_room($id) {
        $segment = $this->find_by_id($id);
        if ($segment === null) {
            return null;
        }

        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `rooms` WHERE id = ? LIMIT 1"
        );
        $statement->bindValue(1, $segment->from_room_id);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : new Room($row);
    }

    public function get_to_room($id) {
        $segment = $this->find_by_id($id);
        if ($segment === null) {
            return null;
        }

        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `rooms` WHERE id = ? LIMIT 1"
        );
        $statement->bindValue(1, $segment->to_room_id);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : new Room($row);
    }

}
