<?php
namespace App\models;

use App\core\Model;

class ExamRequest extends Model
{
    public $id = 0;
    public $subjectName = '';
    public $teacherName = '';
    public $studentCount = 0;
    public $examDate = '';
    public $startTime = '';
    public $endTime = '';
    public $deliveryMode = 'physical';
    public $needsProjector = false;
    public $needsSecondProjector = false;
    public $needsCamera = false;
    public $needsGraphicTablet = false;
    public $neededMarkers = 0;
    public $neededPaperSheets = 0;
    public $onlinePlatform = '';
    public $onlineAccessKey = '';
    public $status = 'pending';
}
