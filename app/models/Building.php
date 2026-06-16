<?php
namespace App\Models;

use App\Core\Model;

class Building extends Model
{
    public $id = 0;
    public $name = '';
    public $code = '';
    public $address = '';
    public $campus_area = '';
    public $floor_count = 0;
    public $is_active = true;
}
