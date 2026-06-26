<?php

namespace App\Repositories;

interface BaseRepository
{
    public function all($orderBy = null, $sortedBy = 'asc');

    public function getWith($relation);

    public function get();

    public function create($data);

    public function createMany($data);

    public function update($model, $data);

    public function delete($model);

    public function groupBy($column_name);

    public function where($key, $value, $operator = null);

    public function whereNotNull($key);

    public function andWhere(array $attributes);

    public function orWhere(array $attributes);

    public function whereIn(string $column_name, $values, string $boolean = 'and', bool $not = false);

    public function whereNotIn(string $column_name, $values, string $boolean = 'and', bool $not = false);

    public function orWhereIn($informationsData);

    public function whereHas($relation, $key, $value);

    public function find($id);

    public function findBySlug($slug);

    public function findByEmail($email);

    public function findByPhone($phone);

    public function findByMany(array $ids);

    public function findByAttributes(array $attributes);

    public function getByAttributes(array $attributes, $orderBy = null, $sortedBy = 'asc');

    public function getByAttributesWithOperators(array $attributes, $orderBy = null, $sortedBy = 'asc');

    public function getInterceptedByAttributes(array $attributes, $orderBy = null, $sortedBy = 'asc');

    public function getInterceptedByAttributes2(array $attributes, $orderBy = null, $sortedBy = 'asc');

    public function getIntercepted(array $attributes, array $andAttributes, $orderBy = null, $sortedBy = 'asc');

    public function getManyToManyRecords(string $relatedModel, $orderBy = null, $sortedBy = 'asc');

    public function getManyToManyRecogetManyToManyRecordsWithPivotAttributes(string $relatedModel, array $attributes, $orderBy = null, $sortedBy = 'asc');

    public function findManyToManyRecordWithPivotAttributes(string $relatedModel, array $attributes);

    public function orderBy($orderBy, $sortedBy = 'asc');

    public function filter(array $attributes, $orderBy = null, $sortedBy = 'asc');

    public function saveImage($file, $path = 'random');

    public function paginate($items, $perPage = 10, $page = null, $options = []);

    public function allPaginated();
}
