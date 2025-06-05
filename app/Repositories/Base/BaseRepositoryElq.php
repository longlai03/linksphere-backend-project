<?php

namespace App\Repositories\Base;

use Illuminate\Support\Collection;

abstract class BaseRepositoryElq implements BaseRepository
{
    protected mixed $_model;

    public function __construct()
    {
        $this->setModel();
    }

    abstract public function getModel(): mixed;

    /**
     * @inheritDoc
     */
    public function setModel(): void
    {
        $model = $this->getModel();
        $this->_model = new $model;
    }

    /**
     * @inheritDoc
     */
    public function select(string|array $select = '*'): mixed
    {
        return $this->_model::select($select);
    }

    /**
     * @inheritDoc
     */
    public function selectRaw(string $selectRaw = '*'): mixed
    {
        return $this->_model::selectRaw($selectRaw);
    }

    /**
     * @inheritDoc
     */
    public function firstOrCreate(array $maps, array $attributes): mixed
    {
        return $this->_model::firstOrCreate($maps, $attributes);
    }

    /**
     * @inheritDoc
     */
    public function getAll(): Collection
    {
        return $this->_model::all();
    }

    /**
     * @inheritDoc
     */
    public function updateOrCreate(array $maps, array $attributes): mixed
    {
        return $this->_model::updateOrCreate($maps, $attributes);
    }

    /**
     * @inheritDoc
     */
    public function findByCondition($condition): mixed
    {
        return $this->_model::where($condition)->first();
    }

    /**
     * @inheritDoc
     */
    public function findOrFail(int $id): mixed
    {
        return $this->_model::findOrFail($id);
    }

    /**
     * @inheritDoc
     */
    public function create(array $attributes): mixed
    {
        return $this->_model::create($attributes);
    }

    /**
     * @inheritDoc
     */
    public function insert(array $attributes): bool
    {
        return $this->_model::insert($attributes);
    }

    /**
     * @inheritDoc
     */
    public function insertGetId(array $attributes): int
    {
        return $this->_model::insertGetId($attributes);
    }

    /**
     * @inheritDoc
     */
    public function updateInIds(array $ids, array $attributes): int
    {
        return $this->_model::whereIn('_id', $ids)->update($attributes);
    }

    /**
     * @inheritDoc
     */
    public function deleteInIds(array $ids): int
    {
        return $this->_model::whereIn('_id', $ids)->delete();
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $attributes): mixed
    {
        $result = $this->find($id);
        if ($result) {
            $result->update($attributes);
            return $result;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): mixed
    {
        return $this->_model::find($id);
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        $result = $this->find($id);
        if ($result) {
            return $result->delete();
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function simplePaginate(int $limit = null, array $columns = ['*']): mixed
    {
        return $this->paginate($limit, $columns, "simplePaginate");
    }

    /**
     * @inheritDoc
     */
    public function paginate(int $limit = null, array $columns = ['*'], string $method = "paginate"): mixed
    {
        $limit = is_null($limit) ? config('repository.pagination.limit', 15) : $limit;
        $results = $this->_model::{$method}($limit, $columns);

        return $results->appends(app('request')->query());
    }

    /**
     * @inheritDoc
     */
    public function selectByIds(array $ids): Collection
    {
        return $this->_model::whereIn('_id', $ids)->get();
    }
}
