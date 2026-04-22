<?php

declare(strict_types=1);

class AuditLog extends Model
{
    protected string $table = 'audit_logs';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    public function user(): ?User
    {
        $userId = $this->get('user_id');

        return $userId === null ? null : (new UserRepository())->find((int) $userId);
    }

    public function oldValues(): array
    {
        return $this->decodeJsonField('old_values');
    }

    public function newValues(): array
    {
        return $this->decodeJsonField('new_values');
    }

    public static function record(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): self {
        return (new AuditLogRepository())->record($action, $entityType, $entityId, $oldValues, $newValues, $userId);
    }

    private function decodeJsonField(string $field): array
    {
        $value = $this->get($field);

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
