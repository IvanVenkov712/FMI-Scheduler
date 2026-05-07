<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Resource;

class ResourceRepository extends Repository {
    protected $model_class = Resource::class;
    protected $table = 'resources';
}
