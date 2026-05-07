<?php
namespace App\Controllers;

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
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        return $this->respond('import-export/import', []);
    }

    public function export()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        return $this->respond('import-export/export', []);
    }

    public function import_csv()
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $result = $this->import_service->import_csv($data['file_path'] ?? '', $data['type'] ?? '');

        return $this->json($result, $result['success'] ? 200 : 422);
    }

    public function import_xml()
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $result = isset($data['xml'])
            ? $this->import_service->import_xml_string($data['xml'])
            : $this->import_service->import_xml($data['file_path'] ?? '');

        return $this->json($result, $result['success'] ? 200 : 422);
    }

    public function import_ascii_plan()
    {
        if ($guard = $this->guarded(['admin', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $result = isset($data['file_path'])
            ? $this->import_service->import_ascii_plan_file($data['floor_id'] ?? 0, $data['file_path'], $data['sync_rooms'] ?? true)
            : $this->import_service->import_ascii_plan($data['floor_id'] ?? 0, $data['ascii_plan'] ?? '', $data['sync_rooms'] ?? true);

        return $this->json($result, $result['success'] ? 200 : 422);
    }

    public function export_floor($floor_id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $path = $this->export_service->export_floor_text_file($this->resolve_id($floor_id));

        return $path === null ? $this->not_found('Floor not found.') : $this->success(['path' => $path]);
    }

    public function export_building($building_id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $path = $this->export_service->export_building_text_file($this->resolve_id($building_id, 'building_id'));

        return $path === null ? $this->not_found('Building not found.') : $this->success(['path' => $path]);
    }

    public function export_schedule($schedule_id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $path = $this->export_service->export_schedule_text_file($this->resolve_id($schedule_id, 'schedule_id'));

        return $path === null ? $this->not_found('Schedule not found.') : $this->success(['path' => $path]);
    }

    public function export_xml()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $data = $this->payload();
        if (isset($data['filename'])) {
            return $this->success(['path' => $this->export_service->export_to_xml_file($data['filename'])]);
        }

        return $this->json(['xml' => $this->export_service->export_xml()]);
    }

    private function resolve_id($id, $key = 'id')
    {
        if ($id !== null && is_numeric($id)) {
            return (int) $id;
        }

        return $this->int_param($key, $this->payload()[$key] ?? 0);
    }
}
