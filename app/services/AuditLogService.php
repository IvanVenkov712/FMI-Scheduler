<?php
namespace App\Services;

use App\Helpers\SensitiveDataHelper;
use App\Logger\Logger;
use App\Models\AuditLog;
use App\Repositories\AuditLogRepository;
use Throwable;

class AuditLogService
{
    private $repository;

    public function __construct($repository = null)
    {
        $this->repository = $repository ?? new AuditLogRepository();
    }

    public function record($request, $user_id, $username, $details = [])
    {
        $method = strtoupper((string) ($request['method'] ?? ''));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        [$entity_type, $entity_id] = $this->entity_reference($request, $details);
        $action = trim(implode('.', array_filter([
            $request['controller'] ?? null,
            $request['action'] ?? null,
        ])));

        $log = new AuditLog([
            'user_id' => $user_id,
            'username' => $username,
            'action' => $action !== '' ? $action : $method . ' ' . ($request['path'] ?? '/'),
            'method' => $method,
            'path' => (string) ($request['path'] ?? '/'),
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'details' => $this->encode_details($request, $details),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            $this->repository->insert($log);
        } catch (Throwable $exception) {
            Logger::warning('Audit log could not be stored', [
                'action' => $log->action,
                'exception' => get_class($exception),
            ]);
        }
    }

    private function entity_reference($request, $details)
    {
        $params = $request['params'] ?? [];
        $entity_id = $params['id'] ?? null;
        $entity_type = $this->entity_type_from_path($request['path'] ?? '');

        foreach ($details as $key => $value) {
            if (is_object($value) && isset($value->id)) {
                return [$key, (string) $value->id];
            }

            if (is_array($value) && isset($value['id'])) {
                return [$key, (string) $value['id']];
            }
        }

        return [$entity_type, $entity_id === null ? null : (string) $entity_id];
    }

    private function entity_type_from_path($path)
    {
        $segment = explode('/', trim((string) $path, '/'))[0] ?? '';

        return $segment === '' ? null : rtrim(str_replace('-', '_', $segment), 's');
    }

    private function encode_details($request, $details)
    {
        $safe = $this->compact(SensitiveDataHelper::redact([
            'params' => $request['params'] ?? [],
            'query' => $request['query'] ?? [],
            'body' => $request['body'] ?? [],
            'result' => $this->result_summary($details),
        ]));
        $json = json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? null : $json;
    }

    private function result_summary($details)
    {
        $summary = [];

        foreach ($details as $key => $value) {
            if (is_object($value) && isset($value->id)) {
                $summary[$key] = ['id' => $value->id];
            } elseif (is_array($value) && isset($value['id'])) {
                $summary[$key] = ['id' => $value['id']];
            } elseif (is_scalar($value) || $value === null) {
                $summary[$key] = $value;
            }
        }

        return $summary;
    }

    private function compact($value, $depth = 0)
    {
        if ($depth >= 5) {
            return '[MAX_DEPTH]';
        }

        if (is_array($value)) {
            $result = [];
            foreach (array_slice($value, 0, 50, true) as $key => $item) {
                $result[$key] = $this->compact($item, $depth + 1);
            }

            if (count($value) > 50) {
                $result['_truncated'] = count($value) - 50;
            }

            return $result;
        }

        if (is_string($value) && strlen($value) > 1000) {
            return substr($value, 0, 1000) . ' [TRUNCATED]';
        }

        return $value;
    }
}
