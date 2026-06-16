<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Floor;
use App\Models\Building;
use App\Models\Room;

class FloorRepository extends Repository {
    protected $model_class = Floor::class;
    protected $table = 'floors';

    public function get_building($id) {
        return $this->row(
            "SELECT b.* FROM `buildings` b
            INNER JOIN `floors` f ON f.building_id = b.id
            WHERE f.id = ? LIMIT 1",
            [$id],
            Building::class
        );
    }

    public function get_rooms($id) {
        return $this->rows(
            "SELECT * FROM `rooms` WHERE floor_id = ? ORDER BY `position_code` ASC, `name` ASC",
            [$id],
            Room::class
        );
    }
}
