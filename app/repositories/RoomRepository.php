<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Event;
use App\Models\Floor;
use App\Models\Resource;
use App\Models\RouteSegment;
use App\Models\Room;
use App\Models\RoomResource;

class RoomRepository extends Repository {
    public function __construct($database = null) {
        parent::__construct($database, Room::class, 'rooms');
    }

    public function get_floor($id) {
        $room = $this->find_by_id($id);
        if ($room === null) {
            return null;
        }

        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `floors` WHERE id = ? LIMIT 1"
        );
        $statement->bindValue(1, $room->floor_id);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : new Floor($row);
    }

    public function get_events($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `events` WHERE room_id = ? ORDER BY `start_time` ASC"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map(
            fn ($row) => new Event($row),
            $statement->fetchAll()
        );
    }

    public function get_available_resources($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `room_resources` r
            WHERE r.room_id = ?
            ORDER BY r.id ASC"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map (
            fn ($row) => new RoomResource($row),
            $statement->fetchAll()
        );
    }

    public function has_available_resources($id, $resource_requirements) {
        foreach ($resource_requirements as $requirement) {
            $statement = $this->database->connection()->prepare(
                "SELECT `resource_quantity` FROM `room_resources`
                WHERE room_id = ? AND resource_id = ?
                LIMIT 1"
            );
            $statement->bindValue(1, $id);
            $statement->bindValue(2, $requirement->resource_id);
            $statement->execute();

            $available_quantity = $statement->fetchColumn();

            if ($available_quantity === false || $available_quantity < $requirement->required_quantity) {
                return false;
            }
        }

        return true;
    }

    // updates the quantity of the resource for the room.
    // if the resource was not present, adds it in the room_resource table
    // if $new_quantity = 0, removes the resource for the room.
    public function update_resource($room_id, $resource_id, $new_quantity) {
        
        if ($new_quantity <= 0) {
            $statement = $this->database->connection()->prepare(
                "DELETE FROM `room_resources` WHERE room_id = ? AND resource_id = ?"
            );
            $statement->bindValue(1, $room_id);
            $statement->bindValue(2, $resource_id);
            $statement->execute();

            return $statement->rowCount() > 0;
        }

        $statement = $this->database->connection()->prepare(
            "UPDATE `room_resources`
            SET resource_quantity = ?
            WHERE room_id = ? AND resource_id = ?"
        );
        $statement->bindValue(1, $new_quantity);
        $statement->bindValue(2, $room_id);
        $statement->bindValue(3, $resource_id);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            return true;
        }

        $statement = $this->database->connection()->prepare(
            "INSERT INTO `room_resources` (`resource_id`, `room_id`, `resource_quantity`)
            VALUES (?, ?, ?)"
        );
        $statement->bindValue(1, $resource_id);
        $statement->bindValue(2, $room_id);
        $statement->bindValue(3, $new_quantity);
        $statement->execute();

        return true;
    }
        
    //checks if there are no events with status "approved" in this time period.
    public function is_room_available($id, $start_time, $end_time) {
        $statement = $this->database->connection()->prepare(
            "SELECT 1 FROM `events`
            WHERE room_id = ?
            AND status = 'approved'
            AND start_time <= ?
            AND end_time >= ?
            LIMIT 1"
        );
        $statement->bindValue(1, $id);
        $statement->bindValue(2, $end_time);
        $statement->bindValue(3, $start_time);
        $statement->execute();

        return $statement->fetchColumn() === false;
    }

    public function get_outgoing_route_segments($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `route_segments` WHERE from_room_id = ?"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map(
            fn ($row) => new RouteSegment($row),
            $statement->fetchAll()
        );
    }

    public function get_ingoing_route_segments($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `route_segments` WHERE to_room_id = ?"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map(
            fn ($row) => new RouteSegment($row),
            $statement->fetchAll()
        );
    }
}
