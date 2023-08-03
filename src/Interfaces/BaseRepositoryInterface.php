<?php

namespace Sandbox\Base\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function find(int $id);
    public function findOne(array $filters = []);
    public function findAndLock(int $id);
    public function create(array $data);
    public function update(array $data, int $id);
    public function updateBy(array $conditions, array $data);
    public function updateOrCreate(array $conditions, array $data);
    public function delete(int $id);
    public function deleteBy(array $conditions);
    public function deleteAll();
    public function translation(Model $model, array $params);
    public function grouping(array $fields = []);
    public function list(array $filters = []);
    public function listPaginated(array $filters = [], array $conditions = []);
}
