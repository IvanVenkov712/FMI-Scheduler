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

    public function 
}