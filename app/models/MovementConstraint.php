<?php
namespace App\Models;

use App\Core\Model;

class MovementConstraint extends Model
{
    public $id = 0;
    public $route_segment_id = 0;
    public $constraint_type = '';
    public $max_capacity = 0;
    public $start_time = '';
    public $end_time = '';
    public $notes = '';
}
