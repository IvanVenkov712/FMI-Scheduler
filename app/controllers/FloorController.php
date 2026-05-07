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
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $data = $this->payload();
        $result = (new ImportService())->import_ascii_plan(
            $this->resolve_id($id),
            $data['ascii_plan'] ?? '',
            !isset($data['sync_rooms']) || filter_var($data['sync_rooms'], FILTER_VALIDATE_BOOLEAN)
        );

        return $this->json($result, $result['success'] ? 200 : 422);
    }

    public function export_ascii_plan($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $plan = (new ExportService())->export_floor_ascii_plan($this->resolve_id($id));
        if ($plan === null) {
            return $this->not_found('Floor not found.');
        }

        return $this->json(['ascii_plan' => $plan]);
    }
}
