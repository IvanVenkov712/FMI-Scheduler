<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\RouteSegment;
use App\Models\Room;

class RouteSegmentRepository extends Repository {
    protected $model_class = RouteSegment::class;
    protected $table = 'route_segments';

    public function find_filtered($filters = []) {
        $conditions = [];
        $params = [];

        $this->add_any_like_condition($conditions, $params, [
            'fr.`name`',
            'fr.`position_code`',
            'tr.`name`',
            'tr.`position_code`',
            'ff.`name`',
            'tf.`name`',
            'fb.`name`',
            'tb.`name`',
            'fb.`code`',
            'tb.`code`',
        ], $filters['q'] ?? '');

        $building_id = trim((string) ($filters['building_id'] ?? ''));
        if ($building_id !== '' && ctype_digit($building_id)) {
            $conditions[] = '(fb.`id` = ? OR tb.`id` = ?)';
            $params[] = (int) $building_id;
            $params[] = (int) $building_id;
        }

        $floor_id = trim((string) ($filters['floor_id'] ?? ''));
        if ($floor_id !== '' && ctype_digit($floor_id)) {
            $conditions[] = '(ff.`id` = ? OR tf.`id` = ?)';
            $params[] = (int) $floor_id;
            $params[] = (int) $floor_id;
        }

        $room_id = trim((string) ($filters['segment_room_id'] ?? ''));
        if ($room_id !== '' && ctype_digit($room_id)) {
            $conditions[] = '(rs.`from_room_id` = ? OR rs.`to_room_id` = ?)';
            $params[] = (int) $room_id;
            $params[] = (int) $room_id;
        }

        $from_room_id = trim((string) ($filters['segment_from_room_id'] ?? ''));
        if ($from_room_id !== '' && ctype_digit($from_room_id)) {
            $conditions[] = 'rs.`from_room_id` = ?';
            $params[] = (int) $from_room_id;
        }

        $to_room_id = trim((string) ($filters['segment_to_room_id'] ?? ''));
        if ($to_room_id !== '' && ctype_digit($to_room_id)) {
            $conditions[] = 'rs.`to_room_id` = ?';
            $params[] = (int) $to_room_id;
        }

        $access = trim((string) ($filters['access'] ?? ''));
        if ($access === 'stairs') {
            $conditions[] = 'rs.`is_stairs` = 1';
        } elseif ($access === 'elevator') {
            $conditions[] = 'rs.`is_elevator` = 1';
        } elseif ($access === 'level') {
            $conditions[] = 'rs.`is_stairs` = 0 AND rs.`is_elevator` = 0';
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['available', 'unavailable'], true)) {
            $conditions[] = 'rs.`is_available` = ?';
            $params[] = $status === 'available' ? 1 : 0;
        }

        return $this->rows(
            "SELECT rs.* FROM `{$this->table}` rs
            LEFT JOIN `rooms` fr ON fr.id = rs.from_room_id
            LEFT JOIN `rooms` tr ON tr.id = rs.to_room_id
            LEFT JOIN `floors` ff ON ff.id = fr.floor_id
            LEFT JOIN `floors` tf ON tf.id = tr.floor_id
            LEFT JOIN `buildings` fb ON fb.id = ff.building_id
            LEFT JOIN `buildings` tb ON tb.id = tf.building_id"
            . $this->where_clause($conditions)
            . " ORDER BY fr.`name` ASC, tr.`name` ASC, rs.`id` ASC",
            $params
        );
    }

    public function get_from_room($id) {
        $segment = $this->find_by_id($id);

        return $segment === null ? null : $this->row("SELECT * FROM `rooms` WHERE id = ? LIMIT 1", [$segment->from_room_id], Room::class);
    }

    public function get_to_room($id) {
        $segment = $this->find_by_id($id);

        return $segment === null ? null : $this->row("SELECT * FROM `rooms` WHERE id = ? LIMIT 1", [$segment->to_room_id], Room::class);
    }

}
