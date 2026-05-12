<?php

namespace App\Models;

use App\Core\Model;

class OnlineAccess extends Model
{
    public $id = 0;
    public $platform = '';
    public $meeting_link = '';
    public $access_key = '';
    public $host_notes = '';
}
