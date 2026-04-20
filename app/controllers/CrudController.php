<?php

declare(strict_types=1);

abstract class CrudController extends Controller
{
    protected string $table = '';
    protected string $viewPath = '';
    protected string $entityName = 'record';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $required = [];
    protected array $jsonFields = [];
    protected array $integerFields = [];
    protected array $booleanFields = [];
    protected array $listFilters = [];
    protected string $defaultOrder = 'id DESC';

    public function index(): mixed
    {
        return $this->respond($this->viewPath . '/index', [
            $this->collectionKey() => $this->rows(),
            'filters' => $this->filters(),
        ]);
    }

    public function create(): string
    {
        return $this->render($this->viewPath . '/create', $this->formData());
    }

    public function store(): mixed
    {
        $data = $this->payload();
        $errors = $this->validate($data);

        if ($errors !== []) {
            return $this->validationResponse($errors, $data, $this->viewPath . '/create');
        }

        $id = $this->insert($this->normalize($data));
        $record = $this->findOrFail($id);

        return $this->storedResponse($record);
    }

    public function details(int|string $id): mixed
    {
        return $this->respond($this->viewPath . '/details', [
            $this->entityName => $this->findOrFail($id),
        ]);
    }

    public function show(int|string $id): mixed
    {
        return $this->details($id);
    }

    public function edit(int|string $id): string
    {
        return $this->render($this->viewPath . '/edit', array_merge($this->formData(), [
            $this->entityName => $this->findOrFail($id),
        ]));
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $errors = $this->validate($data, true);

        if ($errors !== []) {
            return $this->validationResponse($errors, $data, $this->viewPath . '/edit', [
                $this->entityName => array_merge($this->findOrFail($id), $data),
            ]);
        }

        $this->updateRow($id, $this->normalize($data));
        $record = $this->findOrFail($id);

        return $this->updatedResponse($record);
    }

    public function delete(int|string $id): mixed
    {
        $this->database()->execute(
            sprintf('DELETE FROM %s WHERE %s = :id', $this->table, $this->primaryKey),
            ['id' => $id]
        );

        return $this->deletedResponse();
    }

    public function destroy(int|string $id): mixed
    {
        return $this->delete($id);
    }

    protected function rows(?string $sql = null, array $params = []): array
    {
        if ($sql !== null) {
            return $this->database()->fetchAll($sql, $params);
        }

        [$where, $params] = $this->whereFromFilters();

        return $this->database()->fetchAll(
            sprintf('SELECT * FROM %s%s ORDER BY %s', $this->table, $where, $this->defaultOrder),
            $params
        );
    }

    protected function findOrFail(int|string $id): array
    {
        $record = $this->database()->fetch(
            sprintf('SELECT * FROM %s WHERE %s = :id LIMIT 1', $this->table, $this->primaryKey),
            ['id' => $id]
        );

        if ($record === null) {
            http_response_code(404);
            throw new RuntimeException(ucfirst($this->entityName) . ' not found.');
        }

        return $record;
    }

    protected function insert(array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        return $this->database()->insert(
            sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, implode(', ', $columns), implode(', ', $placeholders)),
            $data
        );
    }

    protected function updateRow(int|string $id, array $data): void
    {
        if ($data === []) {
            return;
        }

        $assignments = array_map(static fn (string $column): string => "{$column} = :{$column}", array_keys($data));
        $data['record_id'] = $id;

        $this->database()->execute(
            sprintf('UPDATE %s SET %s WHERE %s = :record_id', $this->table, implode(', ', $assignments), $this->primaryKey),
            $data
        );
    }

    protected function payload(): array
    {
        return $this->body() + $this->input();
    }

    protected function validate(array $data, bool $partial = false): array
    {
        $fields = $partial ? array_intersect($this->required, array_keys($data)) : $this->required;
        $errors = $this->requireFields($data, $fields);

        return array_merge($errors, $this->domainErrors($data));
    }

    protected function domainErrors(array $data): array
    {
        return [];
    }

    protected function normalize(array $data): array
    {
        $normalized = [];

        foreach ($this->fillable as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '') {
                $value = null;
            }

            if (in_array($field, $this->integerFields, true) && $value !== null) {
                $value = (int) $value;
            }

            if (in_array($field, $this->booleanFields, true)) {
                $value = $this->boolValue($value);
            }

            if (in_array($field, $this->jsonFields, true) && $value !== null) {
                $value = $this->jsonValue($value);
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }

    protected function formData(): array
    {
        return [];
    }

    protected function respond(string $view, array $data = []): mixed
    {
        if ($this->wantsJson()) {
            return $data;
        }

        return $this->render($view, $data);
    }

    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $path = $this->request()['path'] ?? '';

        return str_contains($accept, 'application/json')
            || str_starts_with((string) $path, '/api')
            || $this->input('format') === 'json';
    }

    protected function validationResponse(array $errors, array $old, string $view, array $extra = []): mixed
    {
        http_response_code(422);
        $data = array_merge($extra, ['errors' => $errors, 'old' => $old]);

        return $this->respond($view, $data);
    }

    protected function storedResponse(array $record): mixed
    {
        if ($this->wantsJson()) {
            http_response_code(201);
            return [$this->entityName => $record, 'message' => 'Created successfully.'];
        }

        return $this->redirect($this->indexUrl());
    }

    protected function updatedResponse(array $record): mixed
    {
        if ($this->wantsJson()) {
            return [$this->entityName => $record, 'message' => 'Updated successfully.'];
        }

        return $this->redirect($this->indexUrl());
    }

    protected function deletedResponse(): mixed
    {
        if ($this->wantsJson()) {
            return ['message' => 'Deleted successfully.'];
        }

        return $this->redirect($this->indexUrl());
    }

    protected function currentUserId(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    protected function boolValue(mixed $value): int
    {
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
        }

        return $value ? 1 : 0;
    }

    protected function jsonValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($value)) {
            json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function indexUrl(): string
    {
        return '/' . str_replace('_', '-', $this->viewPath);
    }

    protected function collectionKey(): string
    {
        return str_replace('-', '_', basename($this->viewPath));
    }

    protected function filters(): array
    {
        return array_intersect_key($this->input(), array_flip($this->listFilters));
    }

    private function whereFromFilters(): array
    {
        $conditions = [];
        $params = [];

        foreach ($this->filters() as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            $conditions[] = "{$field} = :filter_{$field}";
            $params['filter_' . $field] = $value;
        }

        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }
}
