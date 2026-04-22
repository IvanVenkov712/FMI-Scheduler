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
            $this->collectionKey() => $this->sanitize($this->rows()),
            'filters' => $this->filters(),
        ]);
    }

    public function create(): string
    {
        return $this->render($this->viewPath . '/create', $this->formData());
    }

    public function store(): mixed
    {
        throw new BadMethodCallException(static::class . ' must implement store() via a service layer.');
    }

    public function details(int|string $id): mixed
    {
        return $this->respond($this->viewPath . '/details', [
            $this->entityName => $this->sanitize($this->findOrFail($id)),
        ]);
    }

    public function show(int|string $id): mixed
    {
        return $this->details($id);
    }

    public function edit(int|string $id): string
    {
        return $this->render($this->viewPath . '/edit', array_merge($this->formData(), [
            $this->entityName => $this->sanitize($this->findOrFail($id)),
        ]));
    }

    public function update(int|string $id): mixed
    {
        throw new BadMethodCallException(static::class . ' must implement update() via a service layer.');
    }

    public function delete(int|string $id): mixed
    {
        throw new BadMethodCallException(static::class . ' must implement delete() via a service layer.');
    }

    public function destroy(int|string $id): mixed
    {
        return $this->delete($id);
    }

    protected function rows(?string $sql = null, array $params = []): array
    {
        if ($sql !== null) {
            throw new BadMethodCallException(static::class . ' must not query rows directly; use a service layer.');
        }

        return array_map(
            fn (mixed $record): array => $this->recordToArray($record),
            $this->listRecords($this->filters())
        );
    }

    protected function findOrFail(int|string $id): array
    {
        $record = $this->findRecord($id);

        if ($record === null) {
            http_response_code(404);
            throw new RuntimeException(ucfirst($this->entityName) . ' not found.');
        }

        return $record;
    }

    protected function insert(array $data): string
    {
        throw new BadMethodCallException(static::class . ' must not insert directly; use a service layer.');
    }

    protected function updateRow(int|string $id, array $data): void
    {
        throw new BadMethodCallException(static::class . ' must not update rows directly; use a service layer.');
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

    protected function listRecords(array $filters = []): array
    {
        throw new BadMethodCallException(static::class . ' must implement listRecords() via a service layer.');
    }

    protected function findRecord(int|string $id): ?array
    {
        throw new BadMethodCallException(static::class . ' must implement findRecord() via a service layer.');
    }

    protected function respond(string $view, array $data = []): mixed
    {
        if ($this->wantsJson()) {
            return $this->sanitize($data);
        }

        return $this->render($view, $this->sanitize($data));
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
        $data = array_merge($this->sanitize($extra), ['errors' => $errors, 'old' => $this->sanitize($old)]);

        return $this->respond($view, $data);
    }

    protected function storedResponse(array $record): mixed
    {
        $record = $this->sanitize($record);

        if ($this->wantsJson()) {
            http_response_code(201);
            return [$this->entityName => $record, 'message' => 'Created successfully.'];
        }

        return $this->redirect($this->indexUrl());
    }

    protected function updatedResponse(array $record): mixed
    {
        $record = $this->sanitize($record);

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

    protected function sanitize(mixed $data): mixed
    {
        return SensitiveData::strip($data);
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

    private function recordToArray(mixed $record): array
    {
        if (is_array($record)) {
            return $record;
        }

        if (is_object($record) && method_exists($record, 'toArray')) {
            /** @var array $array */
            $array = $record->toArray();

            return $array;
        }

        return (array) $record;
    }
}
