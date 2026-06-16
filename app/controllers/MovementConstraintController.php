<?php
namespace App\Controllers;

use App\Models\MovementConstraint;
use App\Repositories\MovementConstraintRepository;
use App\Services\MovementConstraintService;

class MovementConstraintController extends CrudController
{
    private $movement_constraint_service;

    protected $repository_class = MovementConstraintRepository::class;
    protected $model_class = MovementConstraint::class;
    protected $view_path = 'routes';
    protected $entity_name = 'movement_constraint';
    protected $validation_rules = [
        'route_segment_id' => ['required', 'integer', 'min:1'],
        'constraint_type' => ['required', 'max_length:50'],
        'max_capacity' => ['integer', 'min:0'],
        'start_time' => ['required', 'date'],
        'end_time' => ['required', 'date', 'after:start_time'],
    ];

    public function __construct(
        $request = [],
        $view = null,
        $session = null,
        $repository = null,
        $movement_constraint_service = null,
    ) {
        parent::__construct($request, $view, $session, $repository);
        $this->movement_constraint_service = $movement_constraint_service
            ?? new MovementConstraintService(null, $this->repository);
    }

    public function usable()
    {
        return $this->guarded_response(null, function () {
            $data = $this->payload();
            return $this->success([
                'usable' => $this->movement_constraint_service->is_route_segment_usable(
                    $data['route_segment_id'] ?? null,
                    $data['start_time'] ?? '',
                    $data['end_time'] ?? ''
                ),
            ]);
        });
    }

    public function overlapping()
    {
        return $this->guarded_response(null, function () {
            $data = $this->payload();
            return $this->success([
                'constraints' => $this->repository->find_overlapping_for_segment(
                    $data['route_segment_id'] ?? null,
                    $data['start_time'] ?? '',
                    $data['end_time'] ?? ''
                ),
            ]);
        });
    }
}
