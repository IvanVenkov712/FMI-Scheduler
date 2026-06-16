<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\User;
use App\Models\Event;
use App\Models\OnlineAccess;
use App\Models\Room;
use App\Models\ResourceRequirement;

class EventRepository extends Repository {
    protected $model_class = Event::class;
    protected $table = 'events';

    public function find_filtered($filters = []) {
        $conditions = [];
        $params = [];

        $this->add_any_like_condition($conditions, $params, [
            'e.`name`',
            'e.`subject_name`',
            'e.`type`',
            'e.`delivery_mode`',
            'e.`status`',
            'r.`name`',
            'r.`position_code`',
            'f.`name`',
            'CAST(f.`level` AS CHAR)',
            'b.`name`',
            'b.`code`',
        ], $filters['q'] ?? '');

        $name = trim((string) ($filters['name'] ?? ''));
        if ($name !== '') {
            $conditions[] = '(e.`name` LIKE ? OR e.`subject_name` LIKE ?)';
            $params[] = '%' . $name . '%';
            $params[] = '%' . $name . '%';
        }

        $building_id = trim((string) ($filters['building_id'] ?? ''));
        if ($building_id !== '' && ctype_digit($building_id)) {
            $conditions[] = 'b.`id` = ?';
            $params[] = (int) $building_id;
        }

        $floor_id = trim((string) ($filters['floor_id'] ?? ''));
        if ($floor_id !== '' && ctype_digit($floor_id)) {
            $conditions[] = 'f.`id` = ?';
            $params[] = (int) $floor_id;
        }

        $room_id = trim((string) ($filters['room_id'] ?? ''));
        if ($room_id !== '' && ctype_digit($room_id)) {
            $conditions[] = 'e.`room_id` = ?';
            $params[] = (int) $room_id;
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if (in_array($type, ['lecture', 'exam', 'practice', 'consultation'], true)) {
            $conditions[] = 'e.`type` = ?';
            $params[] = $type;
        }

        $delivery_mode = trim((string) ($filters['delivery_mode'] ?? ''));
        if (in_array($delivery_mode, ['physical', 'online', 'hybrid'], true)) {
            $conditions[] = 'e.`delivery_mode` = ?';
            $params[] = $delivery_mode;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $conditions[] = 'e.`status` = ?';
            $params[] = $status;
        }

        $date_from = trim((string) ($filters['date_from'] ?? ''));
        if ($this->is_date_filter($date_from)) {
            $conditions[] = 'e.`start_time` >= ?';
            $params[] = $date_from . ' 00:00:00';
        }

        $date_to = trim((string) ($filters['date_to'] ?? ''));
        if ($this->is_date_filter($date_to)) {
            $conditions[] = 'e.`start_time` <= ?';
            $params[] = $date_to . ' 23:59:59';
        }

        return $this->rows(
            "SELECT e.* FROM `{$this->table}` e
            LEFT JOIN `rooms` r ON r.id = e.room_id
            LEFT JOIN `floors` f ON f.id = r.floor_id
            LEFT JOIN `buildings` b ON b.id = f.building_id"
            . $this->where_clause($conditions)
            . " ORDER BY e.`start_time` DESC, e.`id` DESC",
            $params
        );
    }

    public function get_requestor($id) {
        return $this->row(
            "SELECT u.* FROM `users` u
            INNER JOIN `events` e ON e.requestor_id = u.id
            WHERE e.id = ? LIMIT 1",
            [$id],
            User::class
        );
    }

    public function get_room($id) {
        return $this->row(
            "SELECT r.* FROM `rooms` r
            INNER JOIN `events` e ON e.room_id = r.id
            WHERE e.id = ? LIMIT 1",
            [$id],
            Room::class
        );
    }

    public function get_resource_requirements($id) {
        return $this->rows(
            "SELECT rr.*, r.name AS resource_name
            FROM `resource_requirements` rr
            LEFT JOIN `resources` r ON r.id = rr.resource_id
            WHERE rr.event_id = ?
            ORDER BY r.name ASC, rr.resource_id ASC",
            [$id],
            ResourceRequirement::class
        );
    }

    public function get_all_events($start_time, $end_time) {
        return $this->rows(
            "SELECT * FROM `{$this->table}`
            WHERE start_time <= ? AND end_time >= ?
            ORDER BY `start_time` ASC",
            [$end_time, $start_time]
        );
    }

    public function get_online_access($id) {
        $event = $this->find_by_id($id);
        if ($event === null || $event->online_access_id === null) {
            return null;
        }

        return $this->row(
            "SELECT * FROM `online_access`
            WHERE online_access.id = ? LIMIT 1",
            [$event->online_access_id],
            OnlineAccess::class
        );
    }

    public function replace_resource_requirements($event_id, $resource_requirements)
    {
        $this->run("DELETE FROM `resource_requirements` WHERE event_id = ?", [$event_id]);

        foreach ($resource_requirements as $requirement) {
            $this->run(
                "INSERT INTO `resource_requirements`
                (`event_id`, `resource_id`, `required_quantity`, `notes`)
                VALUES (?, ?, ?, ?)",
                [$event_id, $requirement->resource_id, $requirement->required_quantity, $requirement->notes]
            );
        }
    }

    public function upsert_online_access($event_id, $online_access)
    {
        $event = $this->find_by_id($event_id);
        if ($event === null) {
            return;
        }

        if ($event->online_access_id !== null) {
            if ($this->get_online_access($event_id) !== null) {
                $online_access->id = $event->online_access_id;
                $this->run(
                    "UPDATE `online_access`
                    SET `platform` = ?, `meeting_link` = ?, `access_key` = ?, `host_notes` = ?
                    WHERE `id` = ?",
                    [
                        $online_access->platform,
                        $online_access->meeting_link,
                        $online_access->access_key,
                        $online_access->host_notes,
                        $event->online_access_id,
                    ]
                );
                return;
            }

            $event->online_access_id = null;
        }

        $statement = $this->run(
            "INSERT INTO `online_access`
            (`platform`, `meeting_link`, `access_key`, `host_notes`)
            VALUES (?, ?, ?, ?)",
            [
                $online_access->platform,
                $online_access->meeting_link,
                $online_access->access_key,
                $online_access->host_notes,
            ]
        );
        $event->online_access_id = $this->database->connection()->lastInsertId();
        $this->update($event_id, $event);
        return;
    }

    public function detach_online_access($event_id)
    {
        $event = $this->find_by_id($event_id);
        if ($event === null || $event->online_access_id === null) {
            return null;
        }

        $online_access_id = $event->online_access_id;
        $event->online_access_id = null;
        $this->update($event_id, $event);

        return $online_access_id;
    }

    private function is_date_filter($value)
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
