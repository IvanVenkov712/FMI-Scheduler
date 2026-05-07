<?php
namespace App\Services;

use App\Models\Building;
use App\Models\Event;
use App\Models\Floor;
use App\Models\Resource;
use App\Models\Room;
use App\Models\Schedule;
use App\Repositories\BuildingRepository;
use App\Repositories\EventRepository;
use App\Repositories\FloorRepository;
use App\Repositories\ResourceRepository;
use App\Repositories\RoomRepository;
use App\Repositories\ScheduleRepository;

class ImportService
{
    private $building_repository;
    private $floor_repository;
    private $room_repository;
    private $event_repository;
    private $schedule_repository;
    private $resource_repository;

    public function __construct(
        $building_repository = null,
        $floor_repository = null,
        $room_repository = null,
        $event_repository = null,
        $schedule_repository = null,
        $resource_repository = null
    ) {
        $this->building_repository = $building_repository ?? new BuildingRepository();
        $this->floor_repository = $floor_repository ?? new FloorRepository();
        $this->room_repository = $room_repository ?? new RoomRepository();
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->schedule_repository = $schedule_repository ?? new ScheduleRepository();
        $this->resource_repository = $resource_repository ?? new ResourceRepository();
    }

    public function import_ascii_plan($floor_id, $ascii_plan, $sync_rooms = true)
    {
        $floor = $this->floor_repository->find_by_id($floor_id);
        if ($floor === null || trim((string) $ascii_plan) === '') {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Floor not found or empty ASCII plan.'],
            ];
        }

        $plan = $this->normalize_newlines($ascii_plan);
        $parsed_rooms = $this->parse_ascii_plan($plan);

        $floor->ascii_plan = $plan;
        $this->floor_repository->update($floor_id, $floor);

        $result = [
            'success' => true,
            'imported' => 0,
            'updated' => 0,
            'errors' => [],
            'rooms' => $parsed_rooms,
        ];

        if (!$sync_rooms) {
            return $result;
        }

        foreach ($parsed_rooms as $room_data) {
            $room = $this->find_room_by_position_code($floor_id, $room_data['position_code']);
            if ($room === null) {
                $room = new Room($room_data + ['floor_id' => $floor_id]);
                $this->room_repository->insert($room);
                $result['imported']++;
                continue;
            }

            $room->name = $room_data['name'];
            $room->room_type = $room_data['room_type'];
            $room->capacity = $room_data['capacity'];
            $room->room_size = $room_data['room_size'];
            $room->is_active = $room_data['is_active'];
            $this->room_repository->update($room->id, $room);
            $result['updated']++;
        }

        return $result;
    }

    public function import_ascii_plan_file($floor_id, $file_path, $sync_rooms = true)
    {
        if (!is_readable($file_path)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['ASCII plan file is not readable.'],
            ];
        }

        return $this->import_ascii_plan($floor_id, file_get_contents($file_path), $sync_rooms);
    }

    public function parse_ascii_plan($ascii_plan)
    {
        preg_match_all('/\[([^\]]+)\]/', (string) $ascii_plan, $matches);
        $rooms = [];

        foreach ($matches[1] as $index => $token) {
            $room = $this->parse_room_token($token, $index + 1);
            if ($room !== null) {
                $rooms[] = $room;
            }
        }

        return $rooms;
    }

    public function import_csv($file_path, $type)
    {
        if (!is_readable($file_path)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['CSV file is not readable.'],
            ];
        }

        $handle = fopen($file_path, 'r');
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['CSV file is empty.'],
            ];
        }

        $headers = array_map(fn ($header) => $this->normalize_key($header), $headers);
        $repository = $this->repository_for_type($type);
        $model_class = $this->model_class_for_type($type);

        if ($repository === null || $model_class === null) {
            fclose($handle);
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Unsupported CSV import type: ' . $type],
            ];
        }

        $result = [
            'success' => true,
            'imported' => 0,
            'errors' => [],
        ];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            $data = $this->combine_csv_row($headers, $row);
            $model = $this->create_model($model_class, $data);

            try {
                $repository->insert($model);
                $result['imported']++;
            } catch (\Throwable $exception) {
                $result['errors'][] = 'Line ' . $line . ': ' . $exception->getMessage();
            }
        }

        fclose($handle);
        $result['success'] = $result['errors'] === [];

        return $result;
    }

    public function import_xml($file_path)
    {
        if (!is_readable($file_path)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['XML file is not readable.'],
            ];
        }

        $xml = simplexml_load_file($file_path);
        if ($xml === false) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Invalid XML file.'],
            ];
        }

        return $this->import_xml_document($xml);
    }

    public function import_xml_string($xml_string)
    {
        $xml = simplexml_load_string($xml_string);
        if ($xml === false) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Invalid XML string.'],
            ];
        }

        return $this->import_xml_document($xml);
    }

    private function import_xml_document($xml)
    {
        $result = [
            'success' => true,
            'imported' => 0,
            'by_type' => [],
            'errors' => [],
        ];

        foreach ($this->xml_import_order() as $type) {
            if (!isset($xml->{$type})) {
                continue;
            }

            $singular = rtrim($type, 's');
            foreach ($xml->{$type}->{$singular} as $node) {
                $data = $this->xml_node_to_array($node);
                $repository = $this->repository_for_type($type);
                $model_class = $this->model_class_for_type($type);

                if ($repository === null || $model_class === null) {
                    continue;
                }

                try {
                    $repository->insert($this->create_model($model_class, $data));
                    $result['imported']++;
                    $result['by_type'][$type] = ($result['by_type'][$type] ?? 0) + 1;
                } catch (\Throwable $exception) {
                    $result['errors'][] = $type . ': ' . $exception->getMessage();
                }
            }
        }

        $result['success'] = $result['errors'] === [];

        return $result;
    }

    private function parse_room_token($token, $index)
    {
        $parts = array_map('trim', explode('|', $token));
        $identity = $parts[0] ?? '';

        if ($identity === '') {
            return null;
        }

        $identity_parts = array_map('trim', explode(':', $identity, 2));
        $position_code = $identity_parts[0];
        $name = $identity_parts[1] ?? $position_code;

        return [
            'name' => $name,
            'position_code' => $position_code !== '' ? $position_code : 'P' . $index,
            'room_type' => $parts[2] ?? 'physical',
            'capacity' => isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : 0,
            'room_size' => $parts[3] ?? 'small',
            'is_active' => !isset($parts[4]) || !in_array(strtolower($parts[4]), ['0', 'false', 'no', 'inactive'], true),
        ];
    }

    private function find_room_by_position_code($floor_id, $position_code)
    {
        foreach ($this->floor_repository->get_rooms($floor_id) as $room) {
            if ((string) $room->position_code === (string) $position_code) {
                return $room;
            }
        }

        return null;
    }

    private function combine_csv_row($headers, $row)
    {
        $data = [];
        foreach ($headers as $index => $header) {
            $data[$header] = $row[$index] ?? null;
        }

        return $data;
    }

    private function create_model($model_class, $data)
    {
        $model = new $model_class();
        $fields = array_keys(get_class_vars($model_class));

        foreach ($data as $key => $value) {
            if (!in_array($key, $fields, true) || $key === 'id') {
                continue;
            }

            $model->$key = $this->cast_value($key, $value);
        }

        return $model;
    }

    private function cast_value($key, $value)
    {
        if ($value === null) {
            return null;
        }

        if (in_array($key, ['building_id', 'floor_id', 'room_id', 'requestor_id', 'online_access_id', 'capacity', 'floor_count', 'student_count', 'level'], true)) {
            return $value === '' ? null : (int) $value;
        }

        if (in_array($key, ['is_active'], true)) {
            return !in_array(strtolower((string) $value), ['0', 'false', 'no', 'inactive'], true);
        }

        return $value;
    }

    private function repository_for_type($type)
    {
        $type = strtolower(trim($type));
        $type = rtrim($type, ' ');

        $map = [
            'building' => $this->building_repository,
            'buildings' => $this->building_repository,
            'floor' => $this->floor_repository,
            'floors' => $this->floor_repository,
            'room' => $this->room_repository,
            'rooms' => $this->room_repository,
            'resource' => $this->resource_repository,
            'resources' => $this->resource_repository,
            'event' => $this->event_repository,
            'events' => $this->event_repository,
            'schedule' => $this->schedule_repository,
            'schedules' => $this->schedule_repository,
        ];

        return $map[$type] ?? null;
    }

    private function model_class_for_type($type)
    {
        $type = strtolower(trim($type));

        $map = [
            'building' => Building::class,
            'buildings' => Building::class,
            'floor' => Floor::class,
            'floors' => Floor::class,
            'room' => Room::class,
            'rooms' => Room::class,
            'resource' => Resource::class,
            'resources' => Resource::class,
            'event' => Event::class,
            'events' => Event::class,
            'schedule' => Schedule::class,
            'schedules' => Schedule::class,
        ];

        return $map[$type] ?? null;
    }

    private function xml_import_order()
    {
        return ['buildings', 'floors', 'rooms', 'resources', 'events', 'schedules'];
    }

    private function xml_node_to_array($node)
    {
        $data = [];
        foreach ($node->children() as $key => $value) {
            $data[$this->normalize_key($key)] = trim((string) $value);
        }

        return $data;
    }

    private function normalize_key($key)
    {
        return strtolower(trim((string) $key));
    }

    private function normalize_newlines($value)
    {
        return str_replace(["\r\n", "\r"], "\n", (string) $value);
    }
}
