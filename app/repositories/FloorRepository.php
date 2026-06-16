<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Floor;
use App\Models\Building;
use App\Models\Room;

class FloorRepository extends Repository {
    protected $model_class = Floor::class;
    protected $table = 'floors';

    public function find_filtered($filters = []) {
        $conditions = [];
        $params = [];

        $this->add_any_like_condition($conditions, $params, [
            'f.`name`',
            'CAST(f.`level` AS CHAR)',
            'b.`name`',
            'b.`code`',
        ], $filters['q'] ?? '');
        $this->add_like_condition($conditions, $params, 'f.`name`', $filters['name'] ?? '');

        $building_id = trim((string) ($filters['building_id'] ?? ''));
        if ($building_id !== '' && ctype_digit($building_id)) {
            $conditions[] = 'f.`building_id` = ?';
            $params[] = (int) $building_id;
        }

        $level = trim((string) ($filters['level'] ?? ''));
        if ($level !== '' && preg_match('/^-?\d+$/', $level)) {
            $conditions[] = 'f.`level` = ?';
            $params[] = (int) $level;
        }

        return $this->rows(
            "SELECT f.* FROM `{$this->table}` f
            LEFT JOIN `buildings` b ON b.id = f.building_id"
            . $this->where_clause($conditions)
            . " ORDER BY b.`name` ASC, f.`level` ASC, f.`name` ASC",
            $params
        );
    }

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
