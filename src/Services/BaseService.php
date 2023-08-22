<?php

namespace Sandbox\Base\Services;

use Sandbox\Base\Repositories\BaseRepository;

use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    protected $repository;
    public function __construct(
        BaseRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function find(int $id, bool $trash = false)
    {
        return $this->repository->find($id, $trash);
    }

    public function findOne(array $filters = [], bool $trash = false)
    {
        return $this->repository->findOne($filters, $trash);
    }

    public function findAndLock(int $id)
    {
        return $this->repository->findAndLock($id);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(array $data, int $id)
    {
        return $this->repository->update($data, $id);
    }

    public function updateBy(array $conditions, array $data)
    {
        return $this->repository->updateBy($conditions, $data);
    }

    public function updateOrCreate(array $conditions, array $data)
    {
        return $this->repository->updateOrCreate($conditions, $data);
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }

    public function deleteBy(array $conditions)
    {
        return $this->repository->deleteBy($conditions);
    }

    public function deleteAll()
    {
        return $this->repository->deleteAll();
    }

    public function translation(Model $model, array $data)
    {
        return $this->repository->translation($model, $data);
    }

    public function grouping(array $fields = [], array $filters = [])
    {
        return $this->repository->grouping($fields, $filters);
    }

    public function list(array $filters = [])
    {
        return $this->repository->list($filters);
    }

    public function listPaginated(array $filters = [], array $conditions = [])
    {
        return $this->repository->listPaginated($filters, $conditions);
    }

    public function nextPriority(): int
    {
        return $this->repository->nextPriority();
    }
}
