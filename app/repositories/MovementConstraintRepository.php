<?php
namespace App\Repositories;

use App\Core\Repository;
use App\Models\MovementConstraint;

class MovementConstraintRepository extends Repository
{
    public function __construct($database = null)
    {
        parent::__construct($database, MovementConstraint::class, 'movement_constraints');
    }

    public function find_overlapping_for_segment($route_segment_id, $start_time, $end_time)
    {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `movement_constraints`
            WHERE route_segment_id = ?
            AND start_time < ?
            AND end_time > ?
            ORDER BY start_time ASC, id ASC"
        );
        $statement->bindValue(1, $route_segment_id);
        $statement->bindValue(2, $end_time);
        $statement->bindValue(3, $start_time);
        $statement->execute();

        return array_map(
            fn ($row) => new MovementConstraint($row),
            $statement->fetchAll()
        );
    }
}
