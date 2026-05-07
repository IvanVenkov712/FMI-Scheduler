<?php
namespace App\Repositories;

use App\Core\Repository;
use App\Models\Building;
use App\Models\Floor;
use App\models\Room;

class BuildingRepository extends Repository {
    protected $model_class = Building::class;
    protected $table = 'buildings';

    public function get_floors($id) {
        return $this->rows(
            "SELECT * FROM `floors` WHERE building_id = ? ORDER BY `level` ASC",
            [$id],
            Floor::class
        );
    }
}
