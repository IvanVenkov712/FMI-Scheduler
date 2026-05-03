<?php

namespace App\models;

use App\Core\Model;

class Room extends Model
{
    public $id = 0;
    public $floor_id = 0;
    public $name = '';
    public $room_type = 'physical';
    public $capacity = 0;
    public $position_code = '';
    public $is_active = true;
}
