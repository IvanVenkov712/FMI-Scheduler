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
    protected $index_filter_keys = ['q', 'name', 'code', 'status'];
    protected $validation_rules = [
        'name' => ['required', 'max_length:150'],
        'code' => ['required', 'max_length:50'],
        'floor_count' => ['integer', 'min:0'],
    ];

    public function floors($id = null)
    {
        return $this->related($id, 'floors', 'get_floors');
    }

    public function vertical($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $building = $this->find_model($id);
        if ($building === null) {
            return $this->not_found('Building not found.');
        }

        $data = $this->repository->get_floors_with_rooms($building->id);

        return $this->respond('buildings/vertical', [
            'building'       => $building,
            'floors'         => $data['floors'],
            'rooms_by_floor' => $data['rooms_by_floor'],
        ]);
    }
}
