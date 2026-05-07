<?php
namespace App\Repositories;
use App\Core\Repository;
use App\models\OnlineAccess;

class OnlineAccessRepository extends Repository {
    protected $model_class = OnlineAccess::class;
    protected $table = 'online_access';
}
