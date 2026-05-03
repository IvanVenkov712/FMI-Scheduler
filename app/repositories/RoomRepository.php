<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Room;

class RoomRepository extends Repository {
    public function __construct($database) {
        parent::__construct($database, Room::class, 'rooms');
    }

    public function get_floor($id) {

    }

    public function get_events($id) {

    }

    public function get_available_resources($id){

    }

    public function has_available_resources($id, $resource_requirements) {

    }

    public function update_resource($room_id, $resource_id, $new_quantity) {
        // updates the quantity of the resource for the room.
        // if the resource was not present, adds it in the room_resource table
        // if $new_quantity = 0, removes the resource for the room.
    }

    public function is_room_available($id, $start_time, $end_time) {
        //checks if there are no events with status "approved" in this time period.
    }

    public function get_outgoing_route_segments($id) {

    }

    public function get_ingoing_route_segments($id) {

    }
}