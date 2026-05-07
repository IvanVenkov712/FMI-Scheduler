<?php
namespace App\Services;

use App\Repositories\BuildingRepository;
use App\Repositories\EventRepository;
use App\Repositories\FloorRepository;
use App\Repositories\ResourceRepository;
use App\Repositories\RoomRepository;
use App\Repositories\ScheduleRepository;

class ExportService
{
    private $building_repository;
    private $floor_repository;
    private $room_repository;
    private $event_repository;
    private $schedule_repository;
    private $resource_repository;
    private $export_directory;

    public function __construct(
        $building_repository = null,
        $floor_repository = null,
        $room_repository = null,
        $event_repository = null,
        $schedule_repository = null,
        $resource_repository = null,
        $export_directory = null
    ) {
        $this->building_repository = $building_repository ?? new BuildingRepository();
        $this->floor_repository = $floor_repository ?? new FloorRepository();
        $this->room_repository = $room_repository ?? new RoomRepository();
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->schedule_repository = $schedule_repository ?? new ScheduleRepository();
        $this->resource_repository = $resource_repository ?? new ResourceRepository();
        $this->export_directory = $export_directory ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'exports';
    }

    public function export_floor_ascii_plan($floor_id)
    {
        $floor = $this->floor_repository->find_by_id($floor_id);
        if ($floor === null) {
            return null;
        }

        if (trim((string) $floor->ascii_plan) !== '') {
            return $this->normalize_newlines($floor->ascii_plan);
        }

        return $this->build_ascii_plan_from_rooms($this->floor_repository->get_rooms($floor_id));
    }

    public function export_building_ascii_plan($building_id)
    {
        $building = $this->building_repository->find_by_id($building_id);
        if ($building === null) {
            return null;
        }

        $sections = [
            'BUILDING ' . $building->code . ' - ' . $building->name,
        ];

        foreach ($this->building_repository->get_floors($building_id) as $floor) {
            $sections[] = implode(PHP_EOL, [
                '',
                'FLOOR ' . $floor->level . ' - ' . $floor->name,
                $this->export_floor_ascii_plan($floor->id) ?? '',
            ]);
        }

        return rtrim(implode(PHP_EOL, $sections)) . PHP_EOL;
    }

    public function export_floor_text_file($floor_id, $filename = null)
    {
        $floor = $this->floor_repository->find_by_id($floor_id);
        if ($floor === null) {
            return null;
        }

        $building = $this->floor_repository->get_building($floor_id);
        $rooms = $this->floor_repository->get_rooms($floor_id);
        $content = [];

        $content[] = 'Floor export';
        $content[] = 'Building: ' . ($building === null ? 'N/A' : $building->name . ' (' . $building->code . ')');
        $content[] = 'Floor: ' . $floor->name . ' / level ' . $floor->level;
        $content[] = '';
        $content[] = 'ASCII plan:';
        $content[] = $this->export_floor_ascii_plan($floor_id) ?? '';
        $content[] = '';
        $content[] = 'Rooms:';

        foreach ($rooms as $room) {
            $content[] = sprintf(
                '- %s [%s], type: %s, capacity: %s, size: %s, active: %s',
                $room->name,
                $room->position_code,
                $room->room_type,
                $room->capacity,
                $room->room_size,
                $room->is_active ? 'yes' : 'no'
            );
        }

        return $this->write_export_file(
            $filename ?? 'floor_' . $floor_id . '_' . date('Ymd_His') . '.txt',
            implode(PHP_EOL, $content) . PHP_EOL
        );
    }

    public function export_building_text_file($building_id, $filename = null)
    {
        $building = $this->building_repository->find_by_id($building_id);
        if ($building === null) {
            return null;
        }

        $content = [];
        $content[] = 'Building export';
        $content[] = 'Name: ' . $building->name;
        $content[] = 'Code: ' . $building->code;
        $content[] = 'Address: ' . $building->address;
        $content[] = 'Campus area: ' . $building->campus_area;
        $content[] = 'Floors: ' . $building->floor_count;
        $content[] = '';
        $content[] = rtrim($this->export_building_ascii_plan($building_id) ?? '');

        return $this->write_export_file(
            $filename ?? 'building_' . $building_id . '_' . date('Ymd_His') . '.txt',
            implode(PHP_EOL, $content) . PHP_EOL
        );
    }

    public function export_schedule_text_file($schedule_id, $filename = null)
    {
        $schedule = $this->schedule_repository->find_by_id($schedule_id);
        if ($schedule === null) {
            return null;
        }

        $content = [];
        $content[] = 'Schedule export';
        $content[] = 'Name: ' . $schedule->name;
        $content[] = 'Period: ' . $schedule->start_date . ' - ' . $schedule->end_date;
        $content[] = 'Description: ' . $schedule->description;
        $content[] = '';
        $content[] = 'Events:';

        foreach ($this->schedule_repository->get_events($schedule_id) as $event) {
            $room = $event->room_id === null ? null : $this->room_repository->find_by_id($event->room_id);
            $content[] = sprintf(
                '- %s (%s), %s - %s, mode: %s, room: %s, students: %s, status: %s',
                $event->name,
                $event->subject_name,
                $event->start_time,
                $event->end_time,
                $event->delivery_mode,
                $room === null ? 'N/A' : $room->name,
                $event->student_count,
                $event->status
            );
        }

        return $this->write_export_file(
            $filename ?? 'schedule_' . $schedule_id . '_' . date('Ymd_His') . '.txt',
            implode(PHP_EOL, $content) . PHP_EOL
        );
    }

    public function export_xml($filename = null)
    {
        $data = [
            'buildings' => $this->models_to_arrays($this->building_repository->find_all()),
            'floors' => $this->models_to_arrays($this->floor_repository->find_all()),
            'rooms' => $this->models_to_arrays($this->room_repository->find_all()),
            'resources' => $this->models_to_arrays($this->resource_repository->find_all()),
            'events' => $this->models_to_arrays($this->event_repository->find_all()),
            'schedules' => $this->models_to_arrays($this->schedule_repository->find_all()),
        ];

        $xml = $this->array_to_xml('schedule_management_export', $data);

        if ($filename === null) {
            return $xml;
        }

        return $this->write_export_file($filename, $xml);
    }

    public function export_to_xml_file($filename = null)
    {
        return $this->export_xml($filename ?? 'export_' . date('Ymd_His') . '.xml');
    }

    public function export_text_file($content, $filename = null)
    {
        return $this->write_export_file(
            $filename ?? 'export_' . date('Ymd_His') . '.txt',
            (string) $content
        );
    }

    private function build_ascii_plan_from_rooms($rooms)
    {
        if ($rooms === []) {
            return '';
        }

        $tokens = array_map(function ($room) {
            return '[' . $room->position_code . ':' . $room->name . '|' . $room->capacity . '|' . $room->room_type . ']';
        }, $rooms);

        $lines = [];
        foreach (array_chunk($tokens, 4) as $row) {
            $lines[] = implode('--', $row);
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function write_export_file($filename, $content)
    {
        if (!is_dir($this->export_directory)) {
            mkdir($this->export_directory, 0775, true);
        }

        $path = $this->export_directory . DIRECTORY_SEPARATOR . basename($filename);
        file_put_contents($path, $this->normalize_newlines($content));

        return $path;
    }

    private function models_to_arrays($models)
    {
        return array_map(fn ($model) => $model->to_array(), $models);
    }

    private function array_to_xml($root_name, $data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<' . $root_name . '>' . PHP_EOL;
        $xml .= $this->xml_nodes($data, 1);
        $xml .= '</' . $root_name . '>' . PHP_EOL;

        return $xml;
    }

    private function xml_nodes($data, $level)
    {
        $xml = '';
        $indent = str_repeat('    ', $level);

        foreach ($data as $key => $value) {
            $tag = $this->xml_tag($key);

            if (is_array($value) && $this->is_list($value)) {
                $item_tag = rtrim($tag, 's');
                $xml .= $indent . '<' . $tag . '>' . PHP_EOL;
                foreach ($value as $item) {
                    $xml .= $indent . '    <' . $item_tag . '>' . PHP_EOL;
                    $xml .= $this->xml_nodes($item, $level + 2);
                    $xml .= $indent . '    </' . $item_tag . '>' . PHP_EOL;
                }
                $xml .= $indent . '</' . $tag . '>' . PHP_EOL;
                continue;
            }

            if (is_array($value)) {
                $xml .= $indent . '<' . $tag . '>' . PHP_EOL;
                $xml .= $this->xml_nodes($value, $level + 1);
                $xml .= $indent . '</' . $tag . '>' . PHP_EOL;
                continue;
            }

            $xml .= $indent . '<' . $tag . '>' . htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</' . $tag . '>' . PHP_EOL;
        }

        return $xml;
    }

    private function xml_tag($key)
    {
        $tag = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $key);
        return preg_match('/^[A-Za-z_]/', $tag) ? $tag : 'item_' . $tag;
    }

    private function is_list($array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function normalize_newlines($value)
    {
        return str_replace(["\r\n", "\r"], "\n", (string) $value);
    }
}
