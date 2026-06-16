<?php
namespace App\Controllers;

use App\Exceptions\InsertException;
use App\Logger\Logger;
use DateTimeImmutable;
use PDOException;
use Throwable;

abstract class CrudController extends BaseController
{
    protected $repository;
    protected $repository_class;
    protected $model_class;
    protected $view_path;
    protected $entity_name = 'item';
    protected $write_roles = ['admin', 'schedule_manager'];
    protected $validation_rules = [];
    protected $field_labels = [];
    protected $index_filter_keys = [];

    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $class = $this->repository_class;
        $this->repository = $repository ?? new $class();
    }

    public function index()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $filters = $this->index_filters();

        return $this->respond($this->view_path . '/index', [
            $this->collection_key() => $this->index_collection($filters),
            'filters' => $filters,
        ] + $this->index_view_data());
    }

    public function show($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $model = $this->find_model($id);
        if ($model === null) {
            return $this->not_found(ucfirst($this->entity_name) . ' not found.');
        }

        return $this->respond($this->view_path . '/details', [
            $this->entity_name => $model,
        ]);
    }

    public function create()
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        return $this->respond($this->view_path . '/create', $this->create_view_data());
    }

    public function store()
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $data = $this->prepare_payload($this->payload());
        if ($errors = $this->validate_payload($data)) {
            return $this->validation_error($errors);
        }

        try {
            $id = $this->repository->insert($this->new_model($data));

            return $this->success([
                $this->entity_name => $this->repository->find_by_id((int) $id),
            ], 201);
        } catch (InsertException|PDOException $exception) {
            return $this->persistence_error($exception, 'save');
        }
    }

    public function edit($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $model = $this->find_model($id);
        if ($model === null) {
            return $this->not_found(ucfirst($this->entity_name) . ' not found.');
        }

        return $this->respond($this->view_path . '/edit', [
            $this->entity_name => $model,
        ] + $this->edit_view_data($model));
    }

    public function update($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        $existing = $this->repository->find_by_id($id);
        if ($existing === null) {
            return $this->not_found(ucfirst($this->entity_name) . ' not found.');
        }

        $data = $this->prepare_payload($this->payload());
        if ($errors = $this->validate_payload(array_merge($existing->to_array(), $data))) {
            return $this->validation_error($errors);
        }

        try {
            $existing->fill($data);
            $this->repository->update($id, $existing);

            return $this->success([
                $this->entity_name => $this->repository->find_by_id($id),
            ]);
        } catch (InsertException|PDOException $exception) {
            return $this->persistence_error($exception, 'save');
        }
    }

    public function destroy($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        try {
            if (!$this->repository->delete($id)) {
                return $this->not_found(ucfirst($this->entity_name) . ' not found.');
            }
        } catch (PDOException $exception) {
            return $this->persistence_error($exception, 'delete');
        }

        return $this->success(['deleted' => true]);
    }

    protected function new_model($data)
    {
        $class = $this->model_class;
        return new $class($data);
    }

    protected function prepare_payload($data)
    {
        return $data;
    }

    protected function create_view_data()
    {
        return [];
    }

    protected function index_view_data()
    {
        return [];
    }

    protected function index_collection($filters)
    {
        return $this->repository->find_filtered($filters);
    }

    protected function index_filters()
    {
        $filters = [];

        foreach ($this->index_filter_keys as $key) {
            $value = $this->input($key, '');
            $filters[$key] = is_array($value) ? '' : trim((string) $value);
        }

        return $filters;
    }

    protected function edit_view_data($model)
    {
        return $this->create_view_data();
    }

    protected function validate_payload($data)
    {
        $errors = [];

        foreach ($this->validation_rules as $field => $rules) {
            $value = $data[$field] ?? null;
            $label = $this->field_label($field);

            foreach ($rules as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
                $empty = $value === null || (is_string($value) && trim($value) === '');

                if ($name === 'required' && $empty) {
                    $errors[$field] = "{$label} is required.";
                } elseif (!$empty && $name === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field] = "{$label} must be a whole number.";
                } elseif (!$empty && $name === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = "{$label} must be a number.";
                } elseif (!$empty && $name === 'min' && is_numeric($value) && (float) $value < (float) $argument) {
                    $errors[$field] = "{$label} must be at least {$argument}.";
                } elseif (!$empty && $name === 'max_length' && mb_strlen((string) $value) > (int) $argument) {
                    $errors[$field] = "{$label} must not exceed {$argument} characters.";
                } elseif (!$empty && $name === 'in' && !in_array((string) $value, explode(',', (string) $argument), true)) {
                    $errors[$field] = "{$label} contains an invalid option.";
                } elseif (!$empty && $name === 'date' && !$this->is_valid_date($value)) {
                    $errors[$field] = "{$label} must be a valid date.";
                } elseif (!$empty && $name === 'after' && isset($data[$argument])
                    && $this->is_valid_date($value) && $this->is_valid_date($data[$argument])
                    && new DateTimeImmutable($value) <= new DateTimeImmutable($data[$argument])) {
                    $errors[$field] = "{$label} must be after {$this->field_label($argument)}.";
                } elseif (!$empty && $name === 'different' && isset($data[$argument]) && (string) $value === (string) $data[$argument]) {
                    $errors[$field] = "{$label} must be different from {$this->field_label($argument)}.";
                }

                if (isset($errors[$field])) {
                    break;
                }
            }
        }

        return $errors;
    }

    private function validation_error($errors)
    {
        return $this->error('Please correct the highlighted information.', 422, $errors);
    }

    private function persistence_error(Throwable $exception, $operation)
    {
        Logger::exception($exception, [
            'controller' => static::class,
            'operation' => $operation,
        ]);

        $database_exception = $this->database_exception($exception);
        $driver_code = $database_exception instanceof PDOException
            ? ($database_exception->errorInfo[1] ?? null)
            : null;

        if ($driver_code === 1062) {
            return $this->error(
                ucfirst($this->entity_name) . ' with the same unique details already exists.',
                409
            );
        }

        if ($driver_code === 1452) {
            return $this->error(
                'A selected related record does not exist. Check the entered IDs.',
                422
            );
        }

        if ($driver_code === 1451) {
            return $this->error(
                ucfirst($this->entity_name) . ' cannot be deleted because it is still in use.',
                409
            );
        }

        if (in_array($driver_code, [1048, 1364], true)) {
            return $this->error('A required value is missing.', 422);
        }

        if (in_array($driver_code, [1264, 1366, 1406], true)) {
            return $this->error('One or more entered values have an invalid format or length.', 422);
        }

        return $this->error(
            ucfirst($this->entity_name) . " could not be {$operation}d. Check the entered information and try again.",
            422
        );
    }

    private function database_exception(Throwable $exception)
    {
        while ($exception->getPrevious() !== null) {
            $exception = $exception->getPrevious();
        }

        return $exception;
    }

    private function field_label($field)
    {
        return $this->field_labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function is_valid_date($value)
    {
        try {
            new DateTimeImmutable((string) $value);
            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function find_model($id)
    {
        return $this->repository->find_by_id($this->resolve_id($id));
    }

    protected function related($id, $key, $method)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        $model = $this->repository->find_by_id($id);
        if ($model === null) {
            return $this->not_found(ucfirst($this->entity_name) . ' not found.');
        }

        return $this->success([
            $this->entity_name => $model,
            $key => $this->repository->$method($id),
        ]);
    }

    protected function collection_key()
    {
        return $this->entity_name . 's';
    }
}
