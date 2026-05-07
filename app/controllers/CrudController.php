<?php
namespace App\Controllers;

abstract class CrudController extends BaseController
{
    protected $repository;
    protected $model_class;
    protected $view_path;
    protected $entity_name = 'item';
    protected $write_roles = ['admin', 'schedule_manager'];

    public function index()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        return $this->respond($this->view_path . '/index', [
            $this->collection_key() => $this->repository->find_all(),
        ]);
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

        return $this->respond($this->view_path . '/create', []);
    }

    public function store()
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $model = $this->new_model($this->payload());
        $id = $this->repository->insert($model);

        return $this->success([
            $this->entity_name => $this->repository->find_by_id((int) $id),
        ], 201);
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
        ]);
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

        $existing->fill($this->payload());
        $this->repository->update($id, $existing);

        return $this->success([
            $this->entity_name => $this->repository->find_by_id($id),
        ]);
    }

    public function destroy($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        if (!$this->repository->delete($id)) {
            return $this->not_found(ucfirst($this->entity_name) . ' not found.');
        }

        return $this->success(['deleted' => true]);
    }

    protected function new_model($data)
    {
        $class = $this->model_class;
        return new $class($data);
    }

    protected function find_model($id)
    {
        return $this->repository->find_by_id($this->resolve_id($id));
    }

    protected function resolve_id($id)
    {
        if ($id !== null && is_numeric($id)) {
            return (int) $id;
        }

        return $this->int_param('id', 0);
    }

    protected function collection_key()
    {
        return $this->entity_name . 's';
    }
}
