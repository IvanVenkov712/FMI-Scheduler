<?php
namespace App\Controllers;

use App\Models\Building;
use App\Repositories\BuildingRepository;

class BuildingController extends CrudController
{
    protected $repository_class = BuildingRepository::class;
    protected $model_class = Building::class;
    protected $view_path = 'buildings';
    protected $entity_name = 'building';

    public function floors($id = null)
    {
        return $this->related($id, 'floors', 'get_floors');
    }
}
