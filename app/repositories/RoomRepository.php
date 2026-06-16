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
    protected $model_class = Room::class;
    protected $table = 'rooms';

    public function find_by_floor($floor_id) {
        return $this->rows(
            "SELECT * FROM `rooms` WHERE floor_id = ? ORDER BY `position_code` ASC, `name` ASC",
            [$floor_id]
        );
    }

    public function get_floor($id) {
        $room = $this->find_by_id($id);

        return $room === null ? null : $this->row("SELECT * FROM `floors` WHERE id = ? LIMIT 1", [$room->floor_id], Floor::class);
    }

    public function get_events($id) {
        return $this->rows("SELECT * FROM `events` WHERE room_id = ? ORDER BY `start_time` ASC", [$id], Event::class);
    }

    public function get_available_resources($id) {
        return $this->rows(
            "SELECT rr.*, r.name AS resource_name
            FROM `room_resources` rr
            LEFT JOIN `resources` r ON r.id = rr.resource_id
            WHERE rr.room_id = ?
            ORDER BY rr.resource_id ASC",
            [$id],
            RoomResource::class
        );
    }

    public function has_available_resources($id, $resource_requirements) {
        foreach ($resource_requirements as $requirement) {
            $available_quantity = $this->value(
                "SELECT `resource_quantity` FROM `room_resources`
                WHERE room_id = ? AND resource_id = ?
                LIMIT 1",
                [$id, $requirement->resource_id]
            );
            if ($available_quantity === null || $available_quantity < $requirement->required_quantity) {
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
            $statement = $this->run("DELETE FROM `room_resources` WHERE room_id = ? AND resource_id = ?", [$room_id, $resource_id]);

            return $statement->rowCount() > 0;
        }

        $statement = $this->run(
            "UPDATE `room_resources`
            SET resource_quantity = ?
            WHERE room_id = ? AND resource_id = ?",
            [$new_quantity, $room_id, $resource_id]
        );

        if ($statement->rowCount() > 0) {
            return true;
        }

        $this->run(
            "INSERT INTO `room_resources` (`resource_id`, `room_id`, `resource_quantity`)
            VALUES (?, ?, ?)",
            [$resource_id, $room_id, $new_quantity]
        );

        return true;
    }
        
    //checks if there are no events with status "approved" in this time period.
    public function is_room_available($id, $start_time, $end_time) {
        return $this->value(
            "SELECT 1 FROM `events`
            WHERE room_id = ?
            AND status = 'approved'
            AND start_time <= ?
            AND end_time >= ?
            LIMIT 1",
            [$id, $end_time, $start_time]
        ) === null;
    }

    public function get_outgoing_route_segments($id) {
        return $this->rows("SELECT * FROM `route_segments` WHERE from_room_id = ?", [$id], RouteSegment::class);
    }

    public function get_ingoing_route_segments($id) {
        return $this->rows("SELECT * FROM `route_segments` WHERE to_room_id = ?", [$id], RouteSegment::class);
    }
}
