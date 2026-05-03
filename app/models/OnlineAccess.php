<?php

namespace App\models;

use App\Core\Model;

class OnlineAccess extends Model
{
    public $id = 0;
    public $event_request_id = 0;
    public $platform = '';
    public $meeting_link = '';
    public $access_key = '';
    public $host_notes = '';
}
