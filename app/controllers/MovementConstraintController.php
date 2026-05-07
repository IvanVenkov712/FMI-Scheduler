<?php
namespace App\Controllers;

use App\Models\MovementConstraint;
use App\Repositories\MovementConstraintRepository;
use App\Services\MovementConstraintService;

class MovementConstraintController extends CrudController
{
    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->repository = $repository ?? new MovementConstraintRepository();
        $this->model_class = MovementConstraint::class;
        $this->view_path = 'routes';
        $this->entity_name = 'movement_constraint';
    }

    protected function collection_key()
    {
        return 'movement_constraints';
    }

    public function usable()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $data = $this->payload();
        $usable = (new MovementConstraintService())->is_route_segment_usable(
            $data['route_segment_id'] ?? null,
            $data['start_time'] ?? '',
            $data['end_time'] ?? ''
        );

        return $this->success(['usable' => $usable]);
    }

    public function overlapping()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $data = $this->payload();

        return $this->success([
            'constraints' => $this->repository->find_overlapping_for_segment(
                $data['route_segment_id'] ?? null,
                $data['start_time'] ?? '',
                $data['end_time'] ?? ''
            ),
        ]);
    }
}
