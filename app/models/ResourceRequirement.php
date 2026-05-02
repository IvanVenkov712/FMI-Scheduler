<?php

namespace App\models;

use App\core\Model;

class ResourceRequirement extends Model
{
    public $id = 0;
    public $examRequestId = 0;
    public $resourceName = '';
    public $requiredQuantity = 1;
    public $notes = '';
}
