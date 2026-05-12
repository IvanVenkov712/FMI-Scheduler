<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\OnlineAccess;

class OnlineAccessRepository extends Repository {
    protected $model_class = OnlineAccess::class;
    protected $table = 'online_access';
}
