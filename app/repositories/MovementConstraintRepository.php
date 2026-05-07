<?php
namespace App\Repositories;

use App\Core\Repository;
use App\Models\MovementConstraint;

class MovementConstraintRepository extends Repository
{
    protected $model_class = MovementConstraint::class;
    protected $table = 'movement_constraints';

    public function find_overlapping_for_segment($route_segment_id, $start_time, $end_time)
    {
        return $this->rows(
            "SELECT * FROM `movement_constraints`
            WHERE route_segment_id = ?
            AND start_time < ?
            AND end_time > ?
            ORDER BY start_time ASC, id ASC",
            [$route_segment_id, $end_time, $start_time],
            MovementConstraint::class
        );
    }
}
