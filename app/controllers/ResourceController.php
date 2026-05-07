<?php
namespace App\Controllers;

use App\Models\Resource;
use App\Repositories\ResourceRepository;

class ResourceController extends CrudController
{
    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->repository = $repository ?? new ResourceRepository();
        $this->model_class = Resource::class;
        $this->view_path = 'resources';
        $this->entity_name = 'resource';
    }

    protected function collection_key()
    {
        return 'resources';
    }
}
