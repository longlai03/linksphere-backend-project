<?php

namespace App\Services\Base;

use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

abstract class BaseServiceImp implements BaseService
{
    protected BaseRepository $_repository;

    /**
     * @inheritDoc
     */
    public function select(string|array $select = '*'): Builder|bool
    {
        try {
            return $this->_repository->select($select);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function selectRaw(string|array $selectRaw = '*'): Builder|bool
    {
        try {
            return $this->_repository->selectRaw($selectRaw);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function firstOrCreate(array $maps, array $attributes = []): mixed
    {
        try {
            return $this->_repository->firstOrCreate($maps, $attributes);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAll(): bool|Collection
    {
        try {
            return $this->_repository->getAll();
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function updateOrCreate(array $maps, array $attributes): mixed
    {
        try {
            return $this->_repository->updateOrCreate($maps, $attributes);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): mixed
    {
        try {
            return $this->_repository->find($id);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function findByCondition($condition): mixed
    {
        try {
            return $this->_repository->findByCondition($condition);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function findOrFail(int $id): mixed
    {
        try {
            return $this->_repository->findOrFail($id);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function create(array $attributes): mixed
    {
        try {
            return $this->_repository->create($attributes);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function insert(array $attributes): bool
    {
        try {
            return $this->_repository->insert($attributes);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function insertGetId(array $attributes): int
    {
        try {
            return $this->_repository->insertGetId($attributes);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function updateInIds(array $ids, array $attributes): int
    {
        try {
            return $this->_repository->updateInIds($ids, $attributes);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteInIds(array $ids): int
    {
        try {
            return $this->_repository->deleteInIds($ids);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $attributes): mixed
    {
        try {
            return $this->_repository->update($id, $attributes);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        try {
            return $this->_repository->delete($id);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function simplePaginate(int $limit = null, array $columns = ['*']): mixed
    {
        try {
            return $this->paginate($limit, $columns, 'simplePaginate');
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function paginate(int $limit = null, array $columns = ['*'], string $method = 'paginate'): mixed
    {
        try {
            return $this->_repository->paginate($limit, $columns, $method);
        } catch (Throwable $e) {
            logger()->error("{$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }
    }
}
