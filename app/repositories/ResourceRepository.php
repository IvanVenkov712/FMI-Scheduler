<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Resource;

class ResourceRepository extends Repository {
    public function __construct($database) {
        parent::__construct($database, Resource::class, 'resources');
    }
}