<?php

namespace App\models;

use App\core\Model;

class OnlineAccess extends Model
{
    public $id = 0;
    public $examRequestId = 0;
    public $platform = '';
    public $meetingLink = '';
    public $accessKey = '';
    public $hostNotes = '';
}
