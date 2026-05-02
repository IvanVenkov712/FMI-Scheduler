<?php

namespace App\models;

use App\core\Model;

class RouteSegment extends Model
{
    public $id = 0;
    public $fromRoomId = 0;
    public $toRoomId = 0;
    public $walkMinutes = 0;
    public $carMinutes = 0;
    public $publicTransportMinutes = 0;
    public $corridorCapacity = 0;
    public $isStairs = false;
    public $isElevator = false;
}
