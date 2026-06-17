<?php
namespace App\Repositories;
use App\Exceptions\InsertException;
use App\Core\Repository;
use App\Models\Floor;
use App\Models\Building;
use App\Models\Room;
use Throwable;

class FloorRepository extends Repository {
    protected $model_class = Floor::class;
    protected $table = 'floors';

    public function insert($model)
    {
        $started_transaction = $this->begin_transaction();

        try {
            $this->assert_building_floor_limit($model->building_id ?? null);
            $id = parent::insert($model);

            if ($started_transaction) {
                $this->commit();
            }

            return $id;
        } catch (Throwable $exception) {
            if ($started_transaction) {
                $this->roll_back();
            }

            throw $exception;
        }
    }

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

    private function assert_building_floor_limit($building_id)
    {
        if (!is_numeric($building_id) || (int) $building_id < 1) {
            return;
        }

        $building_id = (int) $building_id;
        $floor_limit = $this->value(
            "SELECT `floor_count` FROM `buildings` WHERE `id` = ? LIMIT 1 FOR UPDATE",
            [$building_id]
        );

        if ($floor_limit === null) {
            return;
        }

        $current_count = (int) $this->value(
            "SELECT COUNT(*) FROM `floors` WHERE `building_id` = ?",
            [$building_id]
        );
        $floor_limit = (int) $floor_limit;

        if ($current_count >= $floor_limit) {
            throw new InsertException(
                "This building already has the maximum allowed number of floors ({$floor_limit})."
            );
        }
    }
}
