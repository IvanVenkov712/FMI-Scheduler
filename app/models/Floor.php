<?php
namespace App\models;

use App\core\Model;

class Floor extends Model
{
    public $id = 0;
    public $buildingId = 0;
    public $level = 0;
    public  $name = '';
    public $asciiPlan = '';
    public $notes = '';
}
