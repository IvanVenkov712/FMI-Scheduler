<?php
namespace App\Repositories;

use App\Core\Repository;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;

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

    public function get_floors_with_rooms($id) {
        $floors = $this->rows(
            "SELECT * FROM `floors` WHERE building_id = ? ORDER BY `level` ASC",
            [$id],
            Floor::class
        );
        $rooms_by_floor = [];
        if ($floors) {
            $floor_ids = array_map(fn($f) => $f->id, $floors);
            $placeholders = implode(',', array_fill(0, count($floor_ids), '?'));
            $rooms = $this->rows(
                "SELECT * FROM `rooms` WHERE floor_id IN ($placeholders) ORDER BY `position_code` ASC, `name` ASC",
                $floor_ids,
                Room::class
            );
            foreach ($rooms as $r) {
                $rooms_by_floor[$r->floor_id][] = $r;
            }
        }
        return ['floors' => $floors, 'rooms_by_floor' => $rooms_by_floor];
    }
}
