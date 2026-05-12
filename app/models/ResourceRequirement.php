<?php

namespace App\Models;

use App\Core\Model;

class ResourceRequirement extends Model
{
    public $event_id = 0;
    public $resource_id = '';
    public $required_quantity = 1;
    public $notes = '';
}
