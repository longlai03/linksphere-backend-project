<?php

namespace App\Services\Base;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface BaseService
{
    /**
     * @param string|array $select
     *
     * @return Builder | boolean
     */
    public function select(string|array $select = '*'): Builder|bool;

    /**
     * @param string|array $selectRaw
     *
     * @return Builder | boolean
     */
    public function selectRaw(string|array $selectRaw = '*'): Builder|bool;

    /**
     * @return Collection | boolean
     */
    public function getAll(): bool|Collection;

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function find(int $id): mixed;

    /**
     * @param $condition
     *
     * @return mixed
     */
    public function findByCondition($condition): mixed;

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function findOrFail(int $id): mixed;

    /**
     * @param array $attributes
     *
     * @return mixed
     */
    public function create(array $attributes): mixed;

    /**
     * @param array $attributes
     *
     * @return int
     */
    public function insertGetId(array $attributes): int;

    /**
     * @param array $attributes
     *
     * @return bool
     */
    public function insert(array $attributes): bool;

    /**
     * @param int $id
     * @param array $attributes
     *
     * @return mixed
     */
    public function update(int $id, array $attributes): mixed;

    /**
     * @param array $ids
     * @param array $attributes
     *
     * @return int
     */
    public function updateInIds(array $ids, array $attributes): int;

    /**
     * @param array $ids
     *
     * @return int
     */
    public function deleteInIds(array $ids): int;

    /**
     * @param array $maps
     * @param array $attributes
     *
     * @return mixed
     */
    public function updateOrCreate(array $maps, array $attributes): mixed;

    /**
     * @param array $maps
     * @param array $attributes
     *
     * @return mixed
     */
    public function firstOrCreate(array $maps, array $attributes = []): mixed;

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * @param int|null $limit
     * @param array $columns
     * @param string $method
     *
     * @return mixed
     */
    public function paginate(int $limit = null, array $columns = ['*'], string $method = "paginate"): mixed;

    /**
     * @param int|null $limit
     * @param array $columns
     *
     * @return mixed
     */
    public function simplePaginate(int $limit = null, array $columns = ['*']): mixed;
}
