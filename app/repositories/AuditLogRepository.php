<?php

declare(strict_types=1);

class AuditLogRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?AuditLog
    {
        $row = $this->db()->fetch(
            'SELECT * FROM audit_logs WHERE id = :id LIMIT 1',
            ['id' => $id]
        );

        return $row === null ? null : new AuditLog($row);
    }

    public function latest(int $limit = 50): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM audit_logs ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit)
        );

        return array_map(static fn (array $row): AuditLog => new AuditLog($row), $rows);
    }

    public function forUser(int $userId, int $limit = 50): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM audit_logs WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit),
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): AuditLog => new AuditLog($row), $rows);
    }

    public function forEntity(string $entityType, int|string|null $entityId = null, int $limit = 50): array
    {
        $sql = 'SELECT * FROM audit_logs WHERE entity_type = :entity_type';
        $params = ['entity_type' => $entityType];

        if ($entityId !== null && $entityId !== '') {
            $sql .= ' AND entity_id = :entity_id';
            $params['entity_id'] = (string) $entityId;
        }

        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit);

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): AuditLog => new AuditLog($row), $rows);
    }

    public function forAction(string $action, int $limit = 50): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM audit_logs WHERE action = :action ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit),
            ['action' => $action]
        );

        return array_map(static fn (array $row): AuditLog => new AuditLog($row), $rows);
    }

    public function record(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        $oldValues = $oldValues === null ? null : SensitiveData::redact($oldValues);
        $newValues = $newValues === null ? null : SensitiveData::redact($newValues);

        $id = $this->db()->insert(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
             VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent, :created_at)',
            [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId === null ? null : (string) $entityId,
                'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );

        return $this->find($id) ?? new AuditLog();
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
