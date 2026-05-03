<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Floor;
use App\Models\Building;
use App\Models\Room;

class FloorRepository extends Repository {
    public function __construct($database) {
        parent::__construct($database, Floor::class, 'floors');
    }

    public function get_building($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT b.* FROM `buildings` b
            INNER JOIN `floors` f ON f.building_id = b.id
            WHERE f.id = ? LIMIT 1"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : new Building($row);
    }

    public function get_rooms($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `rooms`
            WHERE floor_id = ?
            ORDER BY `position_code` ASC, `name` ASC"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map(
            fn ($row) => new Room($row),
            $statement->fetchAll()
        );
    }
}
