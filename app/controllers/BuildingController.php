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
    protected $validation_rules = [
        'name' => ['required', 'max_length:150'],
        'code' => ['required', 'max_length:50'],
        'floor_count' => ['integer', 'min:0'],
    ];

    public function floors($id = null)
    {
        return $this->related($id, 'floors', 'get_floors');
    }
}
