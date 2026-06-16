<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Resource;

class ResourceRepository extends Repository {
    protected $model_class = Resource::class;
    protected $table = 'resources';

    public function find_filtered($filters = []) {
        $conditions = [];
        $params = [];

        $this->add_any_like_condition($conditions, $params, [
            '`name`',
            '`description`',
        ], $filters['q'] ?? '');
        $this->add_like_condition($conditions, $params, '`name`', $filters['name'] ?? '');

        return $this->rows(
            "SELECT * FROM `{$this->table}`" . $this->where_clause($conditions) . " ORDER BY `name` ASC",
            $params
        );
    }
}
