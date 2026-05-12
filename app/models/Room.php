<?php

namespace App\Models;

use App\Core\Model;

class Room extends Model
{
    public $id = 0;
    public $floor_id = 0;
    public $name = '';    
    public $position_code = '';
    public $room_type = 'physical';
    public $capacity = 0;
    public $room_size = 'small';
    public $is_active = true;
}
