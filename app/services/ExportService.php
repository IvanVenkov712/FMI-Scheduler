<?php
namespace App\Services;

use App\Models\Building;
use App\Models\Event;
use App\Models\Floor;
use App\Models\Resource;
use App\Models\Room;
use App\Models\Schedule;

/**
 * Exports model fields as CSV/XML and human-readable plans/reports as text.
 * Transfers omit relationship tables and are not a database backup format.
 */
class ExportService extends DataTransferService
{
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

        $sections = ['BUILDING ' . $building->code . ' - ' . $building->name];

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
        $content = [
            'Floor export',
            'Building: ' . ($building === null ? 'N/A' : $building->name . ' (' . $building->code . ')'),
            'Floor: ' . $floor->name . ' / level ' . $floor->level,
            '',
            'ASCII plan:',
            $this->export_floor_ascii_plan($floor_id) ?? '',
            '',
            'Rooms:',
        ];

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

        return $this->write_lines($filename ?? $this->dated_filename('floor', $floor_id, 'txt'), $content);
    }

    public function export_building_text_file($building_id, $filename = null)
    {
        $building = $this->building_repository->find_by_id($building_id);
        if ($building === null) {
            return null;
        }

        return $this->write_lines($filename ?? $this->dated_filename('building', $building_id, 'txt'), [
            'Building export',
            'Name: ' . $building->name,
            'Code: ' . $building->code,
            'Address: ' . $building->address,
            'Campus area: ' . $building->campus_area,
            'Floors: ' . $building->floor_count,
            '',
            rtrim($this->export_building_ascii_plan($building_id) ?? ''),
        ]);
    }

    public function export_schedule_text_file($schedule_id, $filename = null)
    {
        $schedule = $this->schedule_repository->find_by_id($schedule_id);
        if ($schedule === null) {
            return null;
        }

        $content = [
            'Schedule export',
            'Name: ' . $schedule->name,
            'Period: ' . $schedule->start_date . ' - ' . $schedule->end_date,
            'Description: ' . $schedule->description,
            '',
            'Events:',
        ];

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

        return $this->write_lines($filename ?? $this->dated_filename('schedule', $schedule_id, 'txt'), $content);
    }

    public function export_xml($filename = null)
    {
        $xml = $this->array_to_xml('schedule_management_export', $this->export_data());

        if ($filename === null) {
            return $xml;
        }

        return $this->write_export_file($filename, $xml);
    }

    public function export_to_xml_file($filename = null)
    {
        return $this->export_xml($filename ?? 'export_' . date('Ymd_His') . '.xml');
    }

    public function export_csv($type, $filename = null)
    {
        $export_type = $this->csv_export_type($type);
        if ($export_type === null) {
            return null;
        }

        $models = $export_type['repository']->find_all();
        $fields = array_keys(get_class_vars($export_type['model']));
        $content = $this->models_to_csv($models, $fields);

        return $this->write_export_file(
            $filename ?? $this->dated_filename($export_type['name'], 'all', 'csv'),
            $content
        );
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

    private function write_lines($filename, $lines)
    {
        return $this->write_export_file($filename, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private function dated_filename($prefix, $id, $extension)
    {
        return $prefix . '_' . $id . '_' . date('Ymd_His') . '.' . $extension;
    }

    private function export_data()
    {
        return [
            'buildings' => $this->models_to_arrays($this->building_repository->find_all()),
            'floors' => $this->models_to_arrays($this->floor_repository->find_all()),
            'rooms' => $this->models_to_arrays($this->room_repository->find_all()),
            'resources' => $this->models_to_arrays($this->resource_repository->find_all()),
            'events' => $this->models_to_arrays($this->event_repository->find_all()),
            'schedules' => $this->models_to_arrays($this->schedule_repository->find_all()),
        ];
    }

    private function models_to_arrays($models)
    {
        return array_map(fn ($model) => $model->to_array(), $models);
    }

    private function models_to_csv($models, $fields)
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $fields);

        foreach ($models as $model) {
            $data = $model->to_array();
            fputcsv($handle, array_map(fn ($field) => $this->csv_value($data[$field] ?? null), $fields));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    private function csv_value($value)
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $value ?? '';
    }

    private function csv_export_type($type)
    {
        $type = rtrim(strtolower(trim((string) $type)), 's');

        return $this->csv_export_types()[$type] ?? null;
    }

    private function csv_export_types()
    {
        return [
            'building' => ['name' => 'buildings', 'repository' => $this->building_repository, 'model' => Building::class],
            'floor' => ['name' => 'floors', 'repository' => $this->floor_repository, 'model' => Floor::class],
            'room' => ['name' => 'rooms', 'repository' => $this->room_repository, 'model' => Room::class],
            'resource' => ['name' => 'resources', 'repository' => $this->resource_repository, 'model' => Resource::class],
            'event' => ['name' => 'events', 'repository' => $this->event_repository, 'model' => Event::class],
            'schedule' => ['name' => 'schedules', 'repository' => $this->schedule_repository, 'model' => Schedule::class],
        ];
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
}
