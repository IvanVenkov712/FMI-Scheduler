<?php
namespace App\Controllers;

use App\Models\Building;
use App\Repositories\BuildingRepository;

class BuildingController extends CrudController
{
    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->repository = $repository ?? new BuildingRepository();
        $this->model_class = Building::class;
        $this->view_path = 'buildings';
        $this->entity_name = 'building';
    }

    public function floors($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        $building = $this->repository->find_by_id($id);
        if ($building === null) {
            return $this->not_found('Building not found.');
        }

        return $this->success([
            'building' => $building,
            'floors' => $this->repository->get_floors($id),
        ]);
    }
}
