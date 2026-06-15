<?php
namespace App\Repositories;

use App\Core\Repository;
use App\Models\AuditLog;

class AuditLogRepository extends Repository
{
    protected $model_class = AuditLog::class;
    protected $table = 'audit_logs';

    public function find_recent($filters = [], $limit = 200)
    {
        $conditions = [];
        $params = [];

        if (($filters['action'] ?? '') !== '') {
            $conditions[] = '`action` LIKE ?';
            $params[] = '%' . $filters['action'] . '%';
        }

        if (($filters['username'] ?? '') !== '') {
            $conditions[] = '`username` LIKE ?';
            $params[] = '%' . $filters['username'] . '%';
        }

        if (($filters['date_from'] ?? '') !== '') {
            $conditions[] = '`created_at` >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (($filters['date_to'] ?? '') !== '') {
            $conditions[] = '`created_at` <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $limit = max(1, min((int) $limit, 500));

        return $this->rows(
            "SELECT * FROM `{$this->table}`{$where} ORDER BY `created_at` DESC, `id` DESC LIMIT {$limit}",
            $params
        );
    }
}
