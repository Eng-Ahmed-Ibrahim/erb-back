<?php

namespace App\Repositories\Order;

use App\Repositories\BaseRepository;

interface OrderRepository extends BaseRepository
{
    public function adminCreate($data);

    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function adminUpdateStatus($model, $data);

    public function addProduct($model, $data);

    public function deleteProduct($model);

    public function updateProduct($model, $data);

    public function execute($model);
    /**
     * @param  array{products: array<int, mixed>, client_id?: ?string, client_type_id: string, department_id: string}  $data
     * @return array{price: float|int, tax: float|int, discount: float|int, total_price: float|int}
     */
    public function reviewOrderPrice(array $data): array;
}
