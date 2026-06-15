<?php
namespace App\Controllers;

use App\Repositories\AuditLogRepository;

class AuditLogController extends BaseController
{
    private $repository;

    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->repository = $repository ?? new AuditLogRepository();
    }

    public function index()
    {
        if ($guard = $this->guarded(['admin'])) {
            return $guard;
        }

        $filters = [
            'action' => trim((string) $this->input('action', '')),
            'username' => trim((string) $this->input('username', '')),
            'date_from' => $this->date_filter($this->input('date_from', '')),
            'date_to' => $this->date_filter($this->input('date_to', '')),
        ];

        return $this->respond('audit-logs/index', [
            'audit_logs' => $this->repository->find_recent($filters),
            'filters' => $filters,
        ]);
    }

    private function date_filter($value)
    {
        $value = trim((string) $value);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }
}
