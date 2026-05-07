<?php
namespace App\Controllers;

use App\Models\MovementConstraint;
use App\Repositories\MovementConstraintRepository;
use App\Services\MovementConstraintService;

class MovementConstraintController extends CrudController
{
    protected $repository_class = MovementConstraintRepository::class;
    protected $model_class = MovementConstraint::class;
    protected $view_path = 'routes';
    protected $entity_name = 'movement_constraint';

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
