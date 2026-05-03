<?php
namespace App\models;

use App\Core\Model;

class Event extends Model {
    public $id = 0;
    public $requestor_id = 0;
    public $room_id = 0;
    public $online_access_id = 0;
    public $name = '';
    public $type = 'lecture';
    public $start_time = '';
    public $end_time = '';
    public $delivery_mode = 'physical';
    public $subject_name = '';
    public $student_count = 0;
    public $status = 'pending';


}

