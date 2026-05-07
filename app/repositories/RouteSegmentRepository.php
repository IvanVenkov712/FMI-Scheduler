<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\RouteSegment;
use App\Models\Room;

class RouteSegmentRepository extends Repository {
    protected $model_class = RouteSegment::class;
    protected $table = 'route_segments';

    public function get_from_room($id) {
        $segment = $this->find_by_id($id);

        return $segment === null ? null : $this->row("SELECT * FROM `rooms` WHERE id = ? LIMIT 1", [$segment->from_room_id], Room::class);
    }

    public function get_to_room($id) {
        $segment = $this->find_by_id($id);

        return $segment === null ? null : $this->row("SELECT * FROM `rooms` WHERE id = ? LIMIT 1", [$segment->to_room_id], Room::class);
    }

}
