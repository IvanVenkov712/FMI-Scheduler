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

        return $userId === null ? null : User::find((int) $userId);
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
        $oldValues = $oldValues === null ? null : SensitiveData::redact($oldValues);
        $newValues = $newValues === null ? null : SensitiveData::redact($newValues);

        $log = new self([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId === null ? null : (string) $entityId,
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        $log->save();

        return $log;
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
