<?php

namespace Sandbox\Base\Repositories;

use Sandbox\Base\Interfaces\BaseRepositoryInterface;
use Sandbox\DBEncryption\Builders\EncryptionEloquentBuilder;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    protected function filters($query, $filters, $order = true)
    {
        /* prevent order, sort, page from being filtered */
        $orderBy = $filters['order'] ?? null;
        $sortBy = $filters['sort'] ?? 'asc';
        unset($filters['order'], $filters['sort'], $filters['page']);

        if (empty($filters)) return $query;

        $dimension = count($filters, COUNT_RECURSIVE) > count($filters) ? 2 : 1;
        if ($dimension === 1) {
            $query->where($filters);
        } else {
            foreach ($filters as $column => $filter) {
                foreach ($filter as $value => $operator) {
                    if ($operator === 'like') {
                        $query->where($column, $operator, '%' . $value . '%');
                    } else {
                        $query->where($column, $operator, $value);
                    }
                }
            }
        }

        if ($order && $orderBy) {
            $query->orderBy($orderBy, $sortBy);
        }

        return $query;
    }

    public function find(int $id, bool $trash = false)
    {
        if ($trash) {
            return $this->model->withTrashed()->find($id);
        }

        return $this->model->find($id);
    }

    public function findOne(array $filters = [], bool $trash = false)
    {
        $query = $this->model->newQuery();

        if ($trash) {
            $query->withTrashed();
        }

        $query = self::filters($query, $filters);
        return $query->first();
    }

    public function findAndLock(int $id)
    {
        return $this->model->where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, int $id): bool
    {
        return $this->find($id)->update($data);
    }

    public function updateBy(array $conditions, array $data)
    {
        $query = self::filters($this->model->newQuery(), $conditions);
        return $query->update($data);
    }

    public function deleteAll()
    {
        /* find if model has soft delete update all deleted_at to current time */
        if (method_exists($this->model, 'bootSoftDeletes')) {
            return $this->model->withTrashed()->update(['deleted_at' => Carbon::now()]);
        }

        return $this->model->truncate();
    }

    public function updateOrCreate(array $conditions, array $data)
    {
        return $this->model->updateOrCreate($conditions, $data);
    }

    public function delete(int $id): bool
    {
        return $this->model->find($id)->delete();
    }

    public function deleteBy(array $conditions)
    {
        $query = self::filters($this->model->newQuery(), $conditions);
        return $query->delete();
    }

    public function translation(Model $model, array $params)
    {
        foreach ($params as $field => $locales) {
            foreach ($locales as $locale => $value) {
                $model->translateOrNew($locale)->$field = $value;
            }
        }

        $model->save();

        return $model;
    }

    public function grouping(array $fields = [], array $filters = [])
    {
        $stringFields = count($fields) === 1 ? '`' . $fields[0] . '`' : '`' . implode('`, `', $fields) . '`';
        $order = $filters['order'] ?? null;
        $sort = $filters['sort'] ?? 'asc';
        $query = $this->model->newQuery();
        $query->selectRaw($stringFields . ', count(*) as total')->groupBy($fields);

        /* Order by */
        if (isset($order) && isset($sort) && in_array($order, $fields)) {
            $query->orderBy($order, $sort);
        }

        return $query->get();
    }

    public function list(array $filters = []): Collection|EncryptionEloquentBuilder
    {
        $order = $filters['order'] ?? 'id';
        $sort = $filters['sort'] ?? 'desc';
        $query = $this->model->newQuery();

        if (isset($filters['from'])) {
            $from = Carbon::createFromFormat('Y-m-d', $filters['from'])->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if (isset($filters['to'])) {
            $to = Carbon::createFromFormat('Y-m-d', $filters['to'])->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        if (isset($filters['active'])) {
            $status = $filters['active'];
            $query->where('is_active', $status);
        }

        if (isset($filters['keyword'])) {
            $columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
            $keyword = $filters['keyword'];
            $query->where(function ($query) use ($keyword, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
            });
        }

        $query->orderBy($order, $sort);

        return $query->get();
    }

    public function listPaginated(array $filters = [], array $conditions = []): LengthAwarePaginator|EncryptionEloquentBuilder
    {
        $order = $filters['order'];
        $sort = $filters['sort'];
        $perPage = $filters['perPage'] ?? 20;

        $query = $this->model->newQuery();

        if (!empty($conditions['trash'])) {
            $query->onlyTrashed();
        }

        if (!empty($conditions)) {
            unset($conditions['trash']);
            $query = self::filters($query, $conditions, false);
        }

        if (isset($filters['from'])) {
            $from = Carbon::createFromFormat('Y-m-d', $filters['from'])->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if (isset($filters['to'])) {
            $to = Carbon::createFromFormat('Y-m-d', $filters['to'])->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        if (isset($filters['active'])) {
            $status = $filters['active'];
            $query->where('is_active', $status);
        }

        if (isset($filters['keyword'])) {
            $columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
            $keyword = $filters['keyword'];
            $query->where(function ($query) use ($keyword, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
            });
        }

        $query->orderBy($order, $sort);

        if (isset($filters['export'])) {
            return $query;
        }

        return $query->paginate($perPage);
    }

    public function nextPriority(): int
    {
        /* check if model has priority column */
        if (in_array('priority', $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable()))) {
            $max = $this->model->max('priority');
            return $max + 1;
        }

        return 0;
    }
}
