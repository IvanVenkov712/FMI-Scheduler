<?php
namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    public $id = 0;
    public $user_id = null;
    public $username = null;
    public $action = '';
    public $method = '';
    public $path = '';
    public $entity_type = null;
    public $entity_id = null;
    public $ip_address = null;
    public $details = null;
    public $created_at = '';
}
