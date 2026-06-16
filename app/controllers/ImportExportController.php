<?php
namespace App\Controllers;

use App\Repositories\BuildingRepository;
use App\Repositories\FloorRepository;
use App\Repositories\ScheduleRepository;
use App\Services\ExportService;
use App\Services\ImportService;

class ImportExportController extends BaseController
{
    private $import_service;
    private $export_service;

    public function __construct($request = [], $view = null, $session = null, $import_service = null, $export_service = null)
    {
        parent::__construct($request, $view, $session);
        $this->import_service = $import_service ?? new ImportService();
        $this->export_service = $export_service ?? new ExportService();
    }

    public function import()
    {
        return $this->guarded_response($this->import_roles(), fn () => $this->respond('import-export/import', [
            'floors' => (new FloorRepository())->find_all(),
        ]));
    }

    public function export()
    {
        return $this->guarded_response(null, fn () => $this->respond('import-export/export', [
            'buildings' => (new BuildingRepository())->find_all(),
            'schedules' => (new ScheduleRepository())->find_all(),
        ]));
    }

    public function import_csv()
    {
        return $this->import_response(fn ($data) => $this->import_service->import_csv(
            $this->uploaded_file_path('file') ?? ($data['file_path'] ?? ''),
            $data['type'] ?? ''
        ));
    }

    public function import_xml()
    {
        return $this->import_response(fn ($data) => trim((string) ($data['xml'] ?? '')) !== ''
            ? $this->import_service->import_xml_string($data['xml'])
            : $this->import_service->import_xml($this->uploaded_file_path('file') ?? ($data['file_path'] ?? ''))
        );
    }

    public function import_ascii_plan()
    {
        return $this->import_response(fn ($data) => isset($data['file_path'])
            ? $this->import_service->import_ascii_plan_file($data['floor_id'] ?? 0, $data['file_path'], $data['sync_rooms'] ?? true)
            : $this->import_service->import_ascii_plan($data['floor_id'] ?? 0, $data['ascii_plan'] ?? '', $data['sync_rooms'] ?? true)
        );
    }

    public function export_floor($floor_id = null)
    {
        return $this->export_file_response(fn () => $this->export_service->export_floor_text_file($this->resolve_id($floor_id)), 'Floor not found.');
    }

    public function export_building($building_id = null)
    {
        return $this->export_file_response(
            fn () => $this->export_service->export_building_text_file($this->resolve_id($building_id, 'building_id')),
            'Building not found.'
        );
    }

    public function export_schedule($schedule_id = null)
    {
        return $this->export_file_response(
            fn () => $this->export_service->export_schedule_text_file($this->resolve_id($schedule_id, 'schedule_id')),
            'Schedule not found.'
        );
    }

    public function export_xml()
    {
        return $this->guarded_response(null, function () {
            $data = $this->payload();
            return isset($data['filename'])
                ? $this->success(['path' => $this->export_service->export_to_xml_file($data['filename'])])
                : $this->json(['xml' => $this->export_service->export_xml()]);
        });
    }

    public function export_csv()
    {
        return $this->guarded_response(null, function () {
            $data = $this->payload();
            return $this->found_result(
                $this->export_service->export_csv($data['type'] ?? '', $data['filename'] ?? null),
                'path',
                'Unsupported CSV export type.'
            );
        });
    }

    private function import_response($callback)
    {
        return $this->guarded_response($this->import_roles(), fn () => $this->json_result($callback($this->payload())));
    }

    private function export_file_response($callback, $not_found_message)
    {
        return $this->guarded_response(null, fn () => $this->found_result($callback(), 'path', $not_found_message));
    }

    private function import_roles()
    {
        return ['admin', 'schedule_manager'];
    }

    private function uploaded_file_path($field)
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return null;
        }

        $file = $_FILES[$field];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        return is_uploaded_file($file['tmp_name']) || is_readable($file['tmp_name'])
            ? $file['tmp_name']
            : null;
    }
}
