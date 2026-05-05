<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\OnlineAccess;

class OnlineAccessRepository extends Repository {
    public function __construct($database = null) {
        parent::__construct($database, OnlineAccess::class, 'online_access');
    }
}