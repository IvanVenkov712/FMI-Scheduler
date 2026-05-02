<?php
namespace App\models;

use App\core\Model;

class ExamAssignment extends Model
{
    public $id = 0;
    public $examRequestId = 0;
    public $roomId = 0;
    public $examDate = '';
    public $startTime = '';
    public $endTime = '';
    public $bufferBeforeMinutes = 0;
    public $bufferAfterMinutes = 0;
    public $allocationStatus = 'draft';
    public $notes = '';
}
