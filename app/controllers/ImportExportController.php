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

            if ($this->wants_json() && !isset($data['filename'])) {
                return $this->json(['xml' => $this->export_service->export_xml()]);
            }

            return $this->export_file_result(
                $this->export_service->export_to_xml_file($data['filename'] ?? null),
                'XML export failed.'
            );
        });
    }

    public function export_csv()
    {
        return $this->guarded_response(null, function () {
            $data = $this->payload();
            $path = $this->export_service->export_csv($data['type'] ?? '', $data['filename'] ?? null);

            return $this->export_file_result($path, 'Unsupported CSV export type.');
        });
    }

    private function import_response($callback)
    {
        return $this->guarded_response($this->import_roles(), fn () => $this->import_result_response($callback($this->payload())));
    }

    private function import_result_response($result)
    {
        if ($this->wants_json()) {
            return $this->json_result($result);
        }

        if (!empty($result['success'])) {
            $this->audit_success($result['data'] ?? ['success' => true]);
            $this->session->flash('success', $this->import_success_message($result));
        } else {
            $this->session->flash('error', $this->import_error_message($result));
        }

        return $this->redirect_back('/import-export/import');
    }

    private function export_file_response($callback, $not_found_message)
    {
        return $this->guarded_response(null, fn () => $this->export_file_result($callback(), $not_found_message));
    }

    private function export_file_result($path, $not_found_message)
    {
        if ($path === null || !is_readable($path)) {
            return $this->not_found($not_found_message);
        }

        if ($this->wants_json()) {
            return $this->success(['path' => $path]);
        }

        return $this->download_file($path);
    }

    private function download_file($path)
    {
        $filename = basename($path);
        $content_type = $this->download_content_type($path);

        if (!headers_sent()) {
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }

        return file_get_contents($path);
    }

    private function download_content_type($path)
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'csv' => 'text/csv; charset=utf-8',
            'xml' => 'application/xml; charset=utf-8',
            default => 'text/plain; charset=utf-8',
        };
    }

    private function import_roles()
    {
        return ['admin', 'schedule_manager'];
    }

    private function import_success_message($result)
    {
        $parts = [];

        if (isset($result['imported'])) {
            $parts[] = (int) $result['imported'] . ' imported';
        }

        if (isset($result['updated'])) {
            $parts[] = (int) $result['updated'] . ' updated';
        }

        return empty($parts)
            ? 'Import completed successfully.'
            : 'Import completed: ' . implode(', ', $parts) . '.';
    }

    private function import_error_message($result)
    {
        $errors = $result['errors'] ?? [];

        if (!is_array($errors) || empty($errors)) {
            return 'Import failed.';
        }

        return 'Import failed: ' . implode(' ', array_map('strval', $errors));
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
