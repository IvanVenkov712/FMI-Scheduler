<?php
namespace App\models;

use App\Core\Model;

class Floor extends Model
{
    public $id = 0;
    public $building_id = 0;
    public $level = 0;
    public  $name = '';
    public $ascii_plan = '';
    public $notes = '';
}
