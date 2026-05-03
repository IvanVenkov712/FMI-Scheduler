<?php
namespace App\models;

use App\Core\Model;

class EventRequest extends Model
{
    public $id = 0;
    public $requestor_id = 0;
    public $event_id = 0;
    public $room_id = 0;
    public $status = 'pending';
}
