<?php
namespace App\Controllers;

use App\Models\Resource;
use App\Repositories\ResourceRepository;

class ResourceController extends CrudController
{
    protected $repository_class = ResourceRepository::class;
    protected $model_class = Resource::class;
    protected $view_path = 'resources';
    protected $entity_name = 'resource';

}
