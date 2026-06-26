<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as SupportCollections;

abstract class EloquentBaseRepository implements BaseRepository
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all($orderBy = null, $sortedBy = 'desc'): Collection
    {
        if ($orderBy != null) {
            return $this->model->orderBy($orderBy, $sortedBy);
        }

        return $this->model->all();
    }

    public function create($data): Model
    {
        return $this->model->create($data);
    }

    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof SupportCollections ? $items : SupportCollections::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function update($model, $data): bool
    {
        if ($model) {
            return $model->update($data);
        }

        return false;
    }

    public function delete($model): bool
    {
        if ($model) {
            return $model->delete();
        }

        return false;
    }

    public function where($key, $value, $operator = null)
    {
        return $this->model->where($key, $operator, $value);
    }

    public function whereNotNull($key)
    {
        return $this->model->whereNotNull($key);
    }

    public function andWhere(array $attributes)
    {
        return $this->buildWhereQuery($attributes, 'where');
    }

    public function orWhere(array $attributes)
    {
        return $this->buildWhereQuery($attributes, 'orWhere');
    }

    private function buildWhereQuery(array $attributes, $typeOfWhere)
    {
        $query = null;
        if (count($attributes) > 0) {
            $query = $this->model->query();

            if ($typeOfWhere == 'orWhere') {
                $query = $this->model->where($attributes[0][0], $attributes[0][1]);
                unset($attributes[0]);
            }

            foreach ($attributes as $key => $value) {
                $query = $this->model->$typeOfWhere($key, $value);
            }
        }

        return $query;
    }

    public function whereIn(string $column_name, $values, string $boolean = 'and', bool $not = false)
    {
        return $this->model->whereIn($column_name, $values, $boolean, $not);
    }

    public function whereNotIn(string $column_name, $values, string $boolean = 'and', bool $not = false)
    {
        return $this->model->whereNotIn($column_name, $values, $boolean, $not);
    }

    public function orWhereIn($informationsData)
    {
        return $this->buildQueryForOrWhereIn($informationsData, 'whereIn');
    }

    public function orWhereNotIn($informationsData)
    {
        return $this->buildQueryForOrWhereIn($informationsData, 'whereNotIn');
    }

    private function buildQueryForOrWhereIn($informationsData, $methodName)
    {
        $query = null;
        if (count($informationsData) > 0) {
            $query = $this->model->$methodName($informationsData[0]['columnName'], $informationsData[0]['data']);
            unset($informationsData[0]);
            $orMethodName = 'or'.ucfirst($methodName);
            foreach ($informationsData as $item) {
                $query = $this->model::$orMethodName($item['columnName'], $item['values']);
            }
        }

        return $query;
    }

    public function whereHas($relation, $key, $value)
    {
        return $this->model->whereHas($relation, function ($query) use ($key, $value) {
            $query->where($key, $value);
        });
    }

    public function getWith($relation)
    {
        return $this->model->with($relation);
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function findBySlug($slug)
    {
        return $this->model->where('slug', $slug);
    }

    public function findByEmail($email)
    {
        return $this->model->where('email', $email);
    }

    public function findByPhone($phone)
    {
        return $this->model->where('phone', $phone);
    }

    public function orderBy($orderBy, $sortedBy = 'asc')
    {
        return $this->model->orderBy($orderBy, $sortedBy);
    }

    public function groupBy($column_name)
    {
        $this->model->groupBy($column_name);
    }

    public function createMany($data)
    {
        return $this->model->createMany($data);
    }

    public function filter(array $attributes, $orderBy = null, $sortedBy = 'asc')
    {
        $query = $this->model->query();

        $query->where(function ($query) use ($attributes) {
            foreach ($attributes as $field => $value) {
                $query = $query->orWhere($field, 'LIKE', $value.'%');
            }
        });
        if ($orderBy !== null) {
            $query->orderBy($orderBy, $sortedBy);
        }

        return $query;
    }

    public function saveImage($file, $path = 'random')
    {
        $path = $file->store('public/'.$path);
        $path = str_replace('public', 'storage', $path);

        return $path;
    }

    public function findByAttributes(array $attributes)
    {
        $query = $this->model->query();
        foreach ($attributes as $key => $attribute) {
            $query->where($key, $attribute);
        }

        return $query->first();
    }

    public function getByAttributes(array $attributes, $orderBy = null, $sortOrder = 'asc'): Collection
    {
        $query = $this->model->query();
        foreach ($attributes as $key => $attribute) {
            $query->where($key, $attribute);
        }
        if ($orderBy != null) {
            $query->orderBy($orderBy, $sortOrder);
        }

        return $query->get();
    }

    public function getByAttributesWithOperators(array $attributes, $orderBy = null, $sortOrder = 'asc'): Collection
    {
        $query = $this->model->query();
        foreach ($attributes as $key => $attribute) {
            $attributeArray = explode(',', $attribute);
            if (isset($attributeArray[1])) {
                $query->where($key, $attributeArray[0], $attributeArray[1]);
            } else {
                $query->where($key, $attribute);
            }
        }
        if ($orderBy != null) {
            $query->orderBy($orderBy, $sortOrder);
        }

        return $query->get();
    }

    public function findByMany(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function getManyToManyRecords(string $relatedModel, $orderBy = null, $sortOrder = 'asc'): Collection
    {
        if (! method_exists($this->model, $relatedModel)) {
            throw new \InvalidArgumentException("Method '$relatedModel' does not exist on model ".get_class($this->model));
        }
        $relation = $this->model->$relatedModel();
        if ($orderBy != null) {
            $relation = $relation->orderBy($orderBy, $sortOrder);
        }

        return $relation->get();
    }

    public function getManyToManyRecogetManyToManyRecordsWithPivotAttributes($relatedModel, array $relatedAttributes, $orderBy = null, $sortOrder = 'asc'): Collection
    {
        if (! method_exists($this->model, $relatedModel)) {
            throw new \InvalidArgumentException("Method '$relatedModel' does not exist on model ".get_class($this->model));
        }
        $relation = $this->model->$relatedModel();
        foreach ($relatedAttributes as $key => $value) {
            $relation = $relation->wherePivot($key, $value);
        }

        if ($orderBy != null) {
            $relation = $relation->orderBy($orderBy, $sortOrder);
        }

        return $relation->get();
    }

    public function findManyToManyRecordWithPivotAttributes($relatedModel, array $relatedAttributes)
    {
        if (! method_exists($this->model, $relatedModel)) {
            throw new \InvalidArgumentException("Method '$relatedModel' does not exist on model ".get_class($this->model));
        }
        $relation = $this->model->$relatedModel();
        foreach ($relatedAttributes as $key => $value) {
            $relation = $relation->wherePivot($key, $value);
        }

        return $relation->first();
    }

    public function allPaginated($orderBy = null, $sortedBy = 'asc', $addationalAttributes = [])
    {
        if ($orderBy != null) {
            $items = $this->model->orderBy($orderBy, $sortedBy);

            if (is_array($addationalAttributes) && count($addationalAttributes) > 0) {
                foreach ($addationalAttributes as $attribute) {
                    $items = $items->orderBy($attribute[0], $attribute[1]);
                }
            }
            $items = $items->get();
        } else {
            $items = $this->model->all();
        }

        return $this->paginate($items);
    }

    public function get()
    {
        return $this->model->get();
    }

    public function getInterceptedByAttributes(array $attributes, $orderBy = null, $sortedBy = 'asc')
    {
        $query = $this->model->query();
        foreach ($attributes as $key => $attribute) {
            $query->where($key, 'LIKE', '%'.$attribute.'%');
        }
        if ($orderBy != null) {
            $query->orderBy($orderBy, $sortedBy);
        }

        return $query->get();
    }

    public function getInterceptedByAttributes2(array $attributes, $orderBy = null, $sortedBy = 'asc')
    {
        $query = $this->model->query();
        foreach ($attributes as $key => $attribute) {
            $query->where($key, 'LIKE', '%'.$attribute.'%');
        }

        if ($orderBy != null) {
            $query->orderBy('is_printed', 'asc');
            $query->orderBy($orderBy, $sortedBy);
        }

        return $query;
    }

    public function getIntercepted(array $attributes, array $andAttributes, $orderBy = null, $sortedBy = 'asc')
    {
        $query = $this->model->query();
        foreach ($attributes as $key => $attribute) {
            $query->where($key, 'LIKE', '%'.$attribute.'%');
        }
        foreach ($andAttributes as $key => $attribute) {
            $query->where($key, $attribute);
        }
        if ($orderBy != null) {
            $query->orderBy($orderBy, $sortedBy);
        }

        return $query->get();
    }
}
