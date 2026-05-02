<?php
namespace App\models;

use App\core\Model;

class Building extends Model
{
    public $id = 0;
    public $name = '';
    public $code = '';
    public $address = '';
    public $campusArea = '';
    public $verticalView = 'north';
    public $floorCount = 0;
    public $isActive = true;
}
