<?php

namespace App\models;

use App\Core\Model;

class RouteSegment extends Model
{
    public $id = 0;
    public $from_room_id = 0;
    public $to_room_id = 0;
    public $distance = 0;
    public $corridor_capacity = 0;
    public $is_stairs = false;
    public $is_elevator = false;
    public $is_available = true;
}
