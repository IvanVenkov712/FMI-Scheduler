<?php
namespace App\Controllers;

use App\Models\Floor;
use App\Repositories\BuildingRepository;
use App\Repositories\FloorRepository;
use App\Services\ExportService;
use App\Services\ImportService;

class FloorController extends CrudController
{
    private $import_service;
    private $export_service;

    protected $repository_class = FloorRepository::class;
    protected $model_class = Floor::class;
    protected $view_path = 'floors';
    protected $entity_name = 'floor';
    protected $validation_rules = [
        'building_id' => ['required', 'integer', 'min:1'],
        'level' => ['required', 'integer'],
        'name' => ['required', 'max_length:100'],
    ];
    protected $field_labels = ['building_id' => 'Building'];

    public function __construct(
        $request = [],
        $view = null,
        $session = null,
        $repository = null,
        $import_service = null,
        $export_service = null,
    ) {
        parent::__construct($request, $view, $session, $repository);
        $this->import_service = $import_service ?? new ImportService();
        $this->export_service = $export_service ?? new ExportService();
    }

    public function rooms($id = null)
    {
        return $this->related($id, 'rooms', 'get_rooms');
    }

    public function plan($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $floor = $this->find_model($id);
        if ($floor === null) {
            return $this->not_found('Floor not found.');
        }

        $building = (new BuildingRepository())->find_by_id($floor->building_id);
        $rooms = (new \App\Repositories\RoomRepository())->find_by_floor($floor->id);

        return $this->respond('floors/plan', [
            'floor'    => $floor,
            'building' => $building,
            'rooms'    => $rooms,
        ]);
    }

    protected function create_view_data()
    {
        return ['buildings' => (new BuildingRepository())->find_all()];
    }

    public function import_ascii_plan($id = null)
    {
        return $this->guarded_response($this->write_roles, function () use ($id) {
            $data = $this->payload();
            return $this->json_result($this->import_service->import_ascii_plan(
                $this->resolve_id($id),
                $data['ascii_plan'] ?? '',
                !isset($data['sync_rooms']) || filter_var($data['sync_rooms'], FILTER_VALIDATE_BOOLEAN)
            ));
        });
    }

    public function export_ascii_plan($id = null)
    {
        return $this->guarded_response(null, function () use ($id) {
            $plan = $this->export_service->export_floor_ascii_plan($this->resolve_id($id));
            return $plan === null ? $this->not_found('Floor not found.') : $this->json(['ascii_plan' => $plan]);
        });
    }
}
