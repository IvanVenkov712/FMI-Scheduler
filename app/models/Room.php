<?php

namespace App\models;

use App\core\Model;

class Room extends Model
{
    public $id = 0;
    public $floor_id = 0;
    public $name = '';
    public $roomType = 'physical';
    public $capacity = 0;
    public $positionCode = '';
    public $hasProjector = false;
    public $hasSecondProjector = false;
    public $hasCamera = false;
    public $hasGraphicTablet = false;
    public $markerCount = 0;
    public $paperSheetCount = 0;
    public $isActive = true;
}
