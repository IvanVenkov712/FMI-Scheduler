<?php
namespace App\Repositories;

use App\Core\Repository;
use App\Models\Building;
use App\Models\Floor;
use App\models\Room;

class BuildingRepository extends Repository {
    public function __construct($database = null) {
        parent::__construct($database, Building::class, 'buildings');
    }

    public function get_floors($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `floors`
            WHERE building_id = ?
            ORDER BY `level` ASC"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map(
            fn ($row) => new Floor($row),
            $statement->fetchAll()
        );
    }
}
