<?php

namespace App\models;

use App\core\Model;

class ResourceRequirement extends Model
{
    public $id = 0;
    public $event_request_id = 0;
    public $resource_id = '';
    public $required_quantity = 1;
    public $notes = '';
}
