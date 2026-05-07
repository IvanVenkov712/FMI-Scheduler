<?php
namespace App\Controllers;

use App\Models\Floor;
use App\Repositories\FloorRepository;
use App\Services\ExportService;
use App\Services\ImportService;

class FloorController extends CrudController
{
    protected $repository_class = FloorRepository::class;
    protected $model_class = Floor::class;
    protected $view_path = 'floors';
    protected $entity_name = 'floor';

    public function rooms($id = null)
    {
        return $this->related($id, 'rooms', 'get_rooms');
    }

    public function import_ascii_plan($id = null)
    {
        return $this->guarded_response($this->write_roles, function () use ($id) {
            $data = $this->payload();
            return $this->json_result((new ImportService())->import_ascii_plan(
                $this->resolve_id($id),
                $data['ascii_plan'] ?? '',
                !isset($data['sync_rooms']) || filter_var($data['sync_rooms'], FILTER_VALIDATE_BOOLEAN)
            ));
        });
    }

    public function export_ascii_plan($id = null)
    {
        return $this->guarded_response(null, function () use ($id) {
            $plan = (new ExportService())->export_floor_ascii_plan($this->resolve_id($id));
            return $plan === null ? $this->not_found('Floor not found.') : $this->json(['ascii_plan' => $plan]);
        });
    }
}
