<?php

namespace App\Repositories\Order\Eloquent;

use App\Events\OrderEvent;
use App\Jobs\PushOrderNotificationJob;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\DeletedOrder;
use App\Models\DeletedOrderProduct;
use App\Models\Department;
use App\Models\DepartmentStore;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeQuantity;
use App\Repositories\Order\OrderRepository;
use App\Repositories\RecipeQuantity\RecipeQuantityRepository;
use App\Repositories\EloquentBaseRepository;
use App\Transformers\Product\CalculateProductCostPrice;
use Carbon\carbon;
use Illuminate\Support\Facades\DB;

class EloquentOrderRepository extends EloquentBaseRepository implements OrderRepository
{
    const DEFAULT_SERVICE_TAX = 12;

    public function __construct(
        private $recipeQuantitiesRepository
    ) {
        parent::__construct(new Order);
        $this->recipeQuantitiesRepository = app(RecipeQuantityRepository::class);
    }

    private function checkAvailability($data)
    {
        $recipeQuantities = [];

        foreach ($data['products'] as $p) {
            $product = Product::find($p['product_id']);
            if ($product->type == 'kitchen') {
                $department = Department::where('type', 'both')->first();
            } else {
                $department = Department::find($data['department_id']);
            }

            $recipes = $product->recipes()->get() ?? [];

            foreach ($recipes as $recipe) {
                $recipeIdToFind = $recipe->id;
                $requiredQuantity = $recipe->pivot->quantity * $p['quantity'];

                if (!isset($recipeQuantities[$recipeIdToFind])) {
                    $recipeQuantities[$recipeIdToFind] = 0;
                }
                $recipeQuantities[$recipeIdToFind] += $requiredQuantity;
            }
        }

        foreach ($recipeQuantities as $recipeId => $totalRequiredQuantity) {
            $recipeStore = $department->recipes->firstWhere('id', $recipeId);

            if ($recipeStore) {
                $recipeInStore = $recipeStore->pivot;

                if ($recipeInStore && ($recipeInStore->quantity >= $totalRequiredQuantity)) {
                    continue;
                } else {
                    // if (in_array($data['department_id'], Order::KITCHEN_DEPARTMENTS)){
                    continue;
                    // }
                    $recipe = Recipe::find($recipeId);

                    return [
                        'status' => false,
                        'message' => $recipe->name . ' ليس موجود أو الكمية غير كافية',
                    ];
                }
            }
        }

        return [
            'status' => true,
            'message' => '',
        ];
    }

    public function adminCreate($data)
    {
        
        if (isset($data['products'])) {
            // $availabilityStatus = $this->checkAvailability($data);

            // if (!$availabilityStatus['status']) {
            //     return $availabilityStatus;
            // }
            return DB::transaction(function () use ($data) {
                $products = $data['products'];
                unset($data['products']);
                $data['user_id'] = auth()->id();

 
                $payment = PaymentMethod::find($data['payment_method_id']);
                $data['payment_method'] = $payment?->type;
                $makeOrder = $this->create($data);
                

                foreach ($products as $p) {
                    $mproduct = Product::find($p['product_id']);

                    if (!$mproduct) {
                        throw new \Exception('Product not found');
                    }

                    OrderProduct::create([
                        'order_id' => $makeOrder->id,
                        'product_id' => $p['product_id'],
                        'quantity' => $p['quantity'],
                        'price' => $mproduct->price * $p['quantity'],
                    ]);
                }

                if (!isset($data['client_id']) && isset($data['client_type_id'])) {

                    $client = Client::firstOrCreate(
                        ['name' => $data['name'] ?? ''],
                        [
                            'client_type_id' => $data['client_type_id'],
                            'phone' => $data['phone'] ?? '',
                        ]
                    );

                    $makeOrder->client_id = $client->id;
                    $makeOrder->save();
                }

                if (isset($data['military_number'])) {
                    $client = Client::findOrFail($data['client_id'] ?? $client->id);
                    $client->update([
                        'military_number' => $data['military_number']
                    ]);
                }

                $this->syncPreviewOrderPricing($makeOrder->fresh(['products']));

                $status = $this->execute($makeOrder->id);

                if (isset($status['status']) && $status['status'] === false) {
                    throw new \Exception('هذا الاوردار تم تنفيذه من قبل');
                }
                return [
                    'status' => true,
                    'orders' => $makeOrder->fresh(),
                ];
            });
        } else {
            return [
                'status' => false,
                'message' => 'المكون غير موجود',
            ];
        }
    }

    public function adminUpdate($model, $data)
    {
        return $this->update($model, $data);
    }

    public function adminDelete($model)
    {
        return $this->delete($model);
    }

    public function adminUpdateStatus($model, $data)
    {
        return DB::transaction(function () use ($model, $data) {
            if ($data['status'] != 'completed') {
                $model->table_number = null;
            }

            if ($data['status'] == 'completed' || $data['status'] == 'closed') {
                $model->update([
                    'closed_at' => carbon::now(),
                ]);
            }

            $model->save();

            if ($data['status'] == 'closed') {
                $model->user_id = auth()->user()->id;
                $model->save();
 
                $this->claculateOrderPrice($model);

                PushOrderNotificationJob::dispatch($model);
            }
            if ($data['status'] == 'returned') {
                $deletedOrder = $this->ensureDeletedOrderExists($model, $data['message'] ?? '');

                $products = OrderProduct::where('order_id', $model->id)->get();
                foreach ($products as $product) {
                    $this->deleteProduct($product);
                }

                $data['price'] = 0;
                $data['tax'] = 0;
                $data['total_price'] = 0;
            }

            $status = $this->update($model, $data);

            return $status;
        });
    }

    public function addProduct($model, $data)
    {
        if (isset($data['products'])) {
            $data['department_id'] = $model->department_id;

            $availabilityStatus = $this->checkAvailability($data);

            if (!$availabilityStatus['status']) {
                return $availabilityStatus;
            }

            foreach ($data['products'] as $index => $p) {
                $mproduct = Product::find($p['product_id']);
                if ($mproduct) {
                    $orderProductQuery = OrderProduct::where('order_id', $model->id)->where('product_id', $data['products'][$index]['product_id']);
                    if ($orderProductQuery->exists()) {
                        $orderProductQuery->update([
                            'quantity' => $orderProductQuery->pluck('quantity')->first() + $p['quantity'],
                            'price' => ($orderProductQuery->pluck('quantity')->first() + $p['quantity']) * $mproduct->price,
                        ]);
                    } else {
                        $product = new OrderProduct;
                        $product->order_id = $model->id;
                        $product->product_id = $p['product_id'];
                        $product->quantity = $p['quantity'];
                        $product->price = $mproduct->price * $p['quantity'];
                        // $product->type = $p['product_type'];
                        $product->save();
                    }
                }
            }
        }
        // execute the order after adding product
        $status = $this->execute($model->id);
        if (isset($status['status']) && $status['status'] == false) {
            return [
                'status' => false,
                'message' => 'هذا الاوردار تم تنفيذه من قبل',
            ];
        }

        return [
            'status' => true,
            'model' => $model,
        ];
    }

    public function ensureDeletedOrderExists($order, $message = '')
    {
        $deletedOrder = DeletedOrder::where('order_id', $order->id)->first();

        if (!$deletedOrder) {
            $deletedOrderColumns = (new DeletedOrder)->getFillable();

            $deletedOrderData = $order->only($deletedOrderColumns);
            $deletedOrderData['deleted_by'] = auth()->user()->id;
            $deletedOrderData['order_id'] = $order->id;
            $deletedOrderData['deletion_note'] = $message;
            $deletedOrderData['status'] = 'pending';

            $deletedOrder = DeletedOrder::create($deletedOrderData);
        }

        return $deletedOrder;
    }

    public function storeDeletedProduct($product, $deletedOrder, $message = ' ')
    {
        DeletedOrderProduct::create([
            'order_product_id' => $product->id,
            'deleted_order_id' => $deletedOrder->id,
            'product_id' => $product->product_id,
            'quantity' => $product->quantity,
            'price' => $product->price,
            'deleted_by' => auth()->user()->id,
            'deletion_note' => $message,
        ]);
    }

    public function deleteProduct($model, $message = '')
    {
        $deletedOrder = $this->ensureDeletedOrderExists($model->order, $message);
        $this->storeDeletedProduct($model, $deletedOrder, $message);

        $model->update([
            'quantity' => 0,
            'is_executed' => 0,
            'executed_quantity' => $model->quantity,
        ]);

        $status = $this->execute($model->order_id);
        $model->delete();

        if ($model->order->products->count() == 0) {
            $model->order->update(['status' => 'closed']);
        }
    }

    public function updateProduct($model, $data)
    {
        $model->quantity = $data['quantity'];
        $model->save();
    }

    public function execute($id)
    {
        $order = Order::with('products')->findOrFail($id);

        $products = $order->products()->whereRaw('quantity <> executed_quantity')->get();

        foreach ($products as $product) {
            $quantityProduct = $product->quantity - $product->executed_quantity;
            $existedQuantity = $product->quantity;
            $product = $product->product;

            // if($product->type == 'kitchen')
            if (in_array($order->department_id, Order::KITCHEN_DEPARTMENTS)) {
                $department = Department::where('type', 'both')->first();
                $this->excuteOrderFrom($product, $department, $quantityProduct, $order);
            } else {
                $department = Department::find($order->department_id);
                $this->excuteOrderFrom($product, $department, $quantityProduct, $order);
            }

            OrderProduct::where('product_id', $product->id)
                ->where('order_id', $order->id)
                ->update([
                    'is_executed' => true,
                    'executed_quantity' => $existedQuantity,
                ]);
        }

        return $order;
    }

    private function claculateOrderPrice($order)
    {
        $orderCostPrice = $order->products()->get()->sum('cost_price');

        $orderPrice = $order->products()->get()->sum('price');

        $profit = $orderPrice - $orderCostPrice;
        $orderClient = $order->client;
        $clientType = $order->clientType;
        $tax = 0;
        $discount = 0;

        $tax = $orderClient === null || $orderClient->tax === null ? $clientType->tax : $orderClient->tax;

        $discount = $orderClient === null || $orderClient->discount === null ? $clientType->discount : $orderClient->discount;

        if ($order->department_id == '3d1e1d26-91ff-40b8-9b2c-139aa79430e9' && !in_array($clientType->id, ['01j593jy0tjzy0ywx3tjb967cd', '01jekdmsnz0sp7xrq6qnxbs133', ClientType::DEPARTMENT_MANAGER_ID])) {
            $tax = ($tax / 100) * $orderPrice;

            $discount = ($discount / 100) * $orderPrice;
            $order->update([
                'price' => $orderPrice,
                'tax' => $tax,
                'discount' => $discount,
                'total_price' => $orderPrice - $discount + $tax,
            ]);

            return;
        }

        $tempTax = $tax;
        if ($discount == 0) {
            $tax = $tax - 12;
        }

        $tax = ($tax / 100) * $orderPrice;

        $discount = ($discount / 100) * $orderPrice * 1.12;

        $operationCost = 0;

        // To be refactored : make a db based logic to be fetched with the model
        if (in_array($clientType->id, [ClientType::DEPARTMENT_MANAGER_ID])) {
            $operationCost = 0.4 * $orderCostPrice;
            $discount = 0.12 * $orderPrice + $profit;
        } else if (in_array($clientType->id, ['01j593jy0tjzy0ywx3tjb967cd', '01jekdmsnz0sp7xrq6qnxbs133', '01j593d664q8dvys5srn35d2z3'])) {
            $discount = 0.12 * $orderPrice + $profit;
        }
        $order->update([
            'price' => $orderPrice,
            'tax' => ($tempTax / 100) * $orderPrice,
            'discount' => $discount,
            'total_price' => 1.12 * $orderPrice - $discount + $tax + $operationCost,
        ]);
    }

    private function excuteOrderFrom($product, $department, $quantityProduct, $order)
    {
        $recipes = $product->recipes()->wherePivot('product_id', $product->id)->get();
        if ($recipes->count() > 0) {
            foreach ($recipes as $recipe) {
                $quantity = $recipe->pivot->quantity;
                $departmentRecipe = $department->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot;

                // if (! in_array($department->id, Order::KITCHEN_DEPARTMENTS)){
                //     if(! ($departmentRecipe->quantity ?? 0 >= $recipe->quantity) && $quantityProduct > 0){
                //         return [
                //             'status' => false,
                //             'message' => "لا يمكن صرف هذه الكمية بسبب عدم وجود كمية كافية من الصنف $recipe->name"
                //         ];
                //     }
                //  }

                $this->UpdateDepartmentRecipeQuantities($recipe, $department, $quantity, $quantityProduct, $product, $order);
                $this->updateDepartmentStore($department, $recipe);
            }
        }
    }

    protected function updateDepartmentStore($department, $recipe)
    {
        $pivotId = $this->getPivotId($department, $recipe);

        $quantities = $this
            ->recipeQuantitiesRepository
            ->getByAttributes(['department_store_id' => $pivotId])
            ->where('remaining', '>=', 0);

        $price = $quantities->sum('total_price');
        $remaining = $quantities->sum('remaining');

        $department->recipes()->updateExistingPivot($recipe->id, [
            'quantity' => $remaining,
            'price' => $price,
        ]);
    }

    private function UpdateDepartmentRecipeQuantities($departmentRecipe, $department, $quantity, $quantityProduct, $product, $order)
    {
        $orderProduct = OrderProduct::where('order_id', '=', $order->id)
            ->where('product_id', '=', $product->id)
            ->first();

        $departmentPivotId = $this->getPivotId($department, $departmentRecipe);

        $quantites = RecipeQuantity::where('department_store_id', $departmentPivotId)
            ->where('remaining', '>=', 0)
            ->orderBy('expire_date', 'asc')
            ->lockForUpdate() // Add row locking
            ->get();

        $quantity = $quantity * $quantityProduct;
        if ($orderProduct->quantity != $orderProduct->executed_quantity) {
            $orderProduct->update([
                'cost_price' => $orderProduct->cost_price + CalculateProductCostPrice::calculateCostPrice($product) * $quantityProduct,
                'executed_quantity' => $orderProduct->quantity,
            ]);
        }

        // Get ledger recorder
        $ledgerRecorder = app(\App\Service\InventoryLedgerRecorder::class);
        $useLedger = config('app.isInventoryLedgerEnabled', false);

        foreach ($quantites as $recipeQuantity) {
            $remaining = $recipeQuantity->remaining;
            $quantityBefore = $remaining; // Capture before change

            if ($remaining >= $quantity) {
                $recipeQuantity->remaining = $remaining - $quantity;
                $recipeQuantity->total_price = $recipeQuantity->remaining * $recipeQuantity->price;
                $recipeQuantity->save();

                // Record to ledger - only if enabled
                if ($useLedger) {
                    $ledgerRecorder->recordOrderConsumption(
                        order: $order,
                        recipe: $departmentRecipe->id,
                        departmentId: $department->id,
                        quantityBefore: $quantityBefore,
                        quantityAfter: $recipeQuantity->remaining,
                        unitPrice: $recipeQuantity->price,
                        recipeQuantityId: $recipeQuantity->id
                    );
                }

                $quantity = 0;
                // if ($recipeQuantity->remaining == 0)
                // {
                //     $recipeQuantity->delete();
                // }

                break;
            } else {
                // $orderProduct->update([
                //     'cost_price' => $orderProduct->cost_price + ($recipeQuantity->price * ($remaining)),
                // ]);

                $recipeQuantity->remaining = 0;
                $recipeQuantity->total_price = $recipeQuantity->remaining * $recipeQuantity->price;
                $recipeQuantity->save();

                // Record to ledger - only if enabled
                if ($useLedger) {
                    $ledgerRecorder->recordOrderConsumption(
                        order: $order,
                        recipe: $departmentRecipe->id,
                        departmentId: $department->id,
                        quantityBefore: $quantityBefore,
                        quantityAfter: 0,
                        unitPrice: $recipeQuantity->price,
                        recipeQuantityId: $recipeQuantity->id
                    );
                }

                // $orderProduct->update([
                //     'cost_price' => $orderProduct->cost_price + ($recipeQuantity->price * ($quantity)),
                // ]);

                $quantity -= $remaining;
                // $recipeQuantity->delete();
            }
        }
        if ($quantity > 0) {
            $deptStoreQuery = DepartmentStore::where('recipe_id', $departmentRecipe->id)
                ->where('department_id', $department->id);

            $recipeQuantity = RecipeQuantity::where('department_store_id', $deptStoreQuery->first()->id)
                // ->orderBy('created_at', 'desc')
                ->orderBy('price', 'desc')
                ->first();

            $price = $recipeQuantity ? $recipeQuantity->price : 0;

            // $orderProduct->update([
            //     'cost_price' => $orderProduct->cost_price + ($price * ($quantity)),
            // ]);

            $deptStoreQuery->update([
                'over_quantity' => $deptStoreQuery->first()->over_quantity + $quantity,
            ]);
        }
    }

    private function syncPreviewOrderPricing(Order $order): void
    {
        if (! $order->client_id || ! $order->client_type_id) {
            return;
        }

        $order->loadMissing('products');
        $products = [];
        foreach ($order->products as $op) {
            $product = Product::find($op->product_id);
            if (! $product) {
                continue;
            }
            $products[] = [
                'productId' => (string) $product->id,
                'price' => $product->price,
                'quantity' => $op->quantity,
            ];
        }
        if ($products === []) {
            return;
        }

        $preview = $this->reviewOrderPrice([
            'products' => $products,
            'client_id' => $order->client_id,
            'client_type_id' => $order->client_type_id,
            'department_id' => $order->department_id,
        ]);

        $order->update([
            'price' => $preview['price'],
            'tax' => $preview['tax'],
            'discount' => $preview['discount'],
            'total_price' => $preview['total_price'],
        ]);
    }

    public function reviewOrderPrice(array $data): array
    {
        $products = collect($data['products']);
        $clientId = $data['client_id'];
        $clientTypeId = $data['client_type_id'];
        $department_id = $data['department_id'];

        $orderCostPrice = $products->sum(fn($product) => CalculateProductCostPrice::calculateCostPrice(Product::find($product['productId'])) * $product['quantity']);
        $orderPrice = $products->sum(fn($product) => $product['price'] * $product['quantity']);
        $profit = $orderPrice - $orderCostPrice;

        $orderClient = Client::find($clientId);
        $clientType = ClientType::find($clientTypeId);

        $tax = $orderClient->tax ?? $clientType->tax ?? 0;
        $discount = $orderClient->discount ?? $clientType->discount ?? 0;
        $hasTax = Department::find(auth()->user()->department_id)?->has_tax;

        $crossOrderPrice = ($department_id == '3d1e1d26-91ff-40b8-9b2c-139aa79430e9' || !$hasTax) ? $orderPrice : 1.12 * $orderPrice;

        if ((!$hasTax && !in_array($clientType->id, ['01j593jy0tjzy0ywx3tjb967cd', '01jekdmsnz0sp7xrq6qnxbs133', ClientType::DEPARTMENT_MANAGER_ID]))) {
            $tax = ($tax / 100) * $orderPrice;

            $discount = ($discount / 100) * $orderPrice;

            return [
                'price' => $crossOrderPrice,
                'tax' => $tax,
                'discount' => $discount,
                'total_price' => $orderPrice - $discount + $tax,
            ];
        }

        $tempTax = $tax;
        if ($discount == 0) {
            $tax = $tax - 12;
        }

        $tax = ($tax / 100) * $orderPrice;
        $discount = ($discount / 100) * $orderPrice * 1.12;
        $operationCost = 0;

        if (in_array($clientType->id, [ClientType::DEPARTMENT_MANAGER_ID])) {
            $operationCost = 0.4 * $orderCostPrice;
            $discount = 0.12 * $orderPrice + $profit;
        } else if (in_array($clientType->id, ['01j593jy0tjzy0ywx3tjb967cd', '01jekdmsnz0sp7xrq6qnxbs133', '01j593d664q8dvys5srn35d2z3'])) {
            $discount = 0.12 * $orderPrice + $profit;
        }

        return [
            'price' => $crossOrderPrice,
            'tax' => ($tempTax / 100) * $orderPrice,
            'discount' => $discount,
            'total_price' => 1.12 * $orderPrice - $discount + $tax + $operationCost,
        ];
    }

    protected function getPivotId($department, $recipe)
    {
        $pivot = DB::table('department_store')
            ->select('id')
            ->where('recipe_id', '=', $recipe->id)
            ->where('department_id', '=', $department->id)
            ->first();

        if (!$pivot) {
            $pivot = DepartmentStore::create([
                'recipe_id' => $recipe->id,
                'department_id' => $department->id,
                'price' => 0,
                'quantity' => 0,
            ]);
        }

        return $pivot->id;
    }

    public function ordersReport($data)
    {
        $departmentsWithOrders = Order::when(isset($data['payment_method_id']), fn($query) => $query
            ->where('payment_method_id', $data['payment_method_id']))
            ->when(isset($data['client_id']), fn($query) => $query
                ->where('client_id', $data['client_id']))
            ->when(isset($data['client_type_id']), fn($query) => $query
                ->where('orders.client_type_id', $data['client_type_id']))  // Fix: Specify table for client_type_id
            ->when(isset($data['from']), fn($query) => $query
                ->where('order_date', '>=', $data['from']))
            ->when(isset($data['to']), fn($query) => $query
                ->where('order_date', '<=', $data['to']))
            ->join('departments', 'orders.department_id', '=', 'departments.id')
            ->where('status', '!=', 'returned')
            ->selectRaw('orders.department_id, departments.name as department_name, 
                    SUM(orders.total_price) as total_order_price')
            ->groupBy('orders.department_id', 'departments.name')
            ->get();

        $departmentsWithOrders = $departmentsWithOrders->map(function ($department) use ($data) {
            $orders = Order::when(isset($data['payment_method_id']), fn($query) => $query
                ->where('payment_method_id', $data['payment_method_id']))
                ->when(isset($data['client_id']), fn($query) => $query
                    ->where('client_id', $data['client_id']))
                ->when(isset($data['client_type_id']), fn($query) => $query
                    ->where('orders.client_type_id', $data['client_type_id']))
                ->when(isset($data['from']), fn($query) => $query
                    ->where('order_date', '>=', $data['from']))
                ->when(isset($data['to']), fn($query) => $query
                    ->where('order_date', '<=', $data['to']))
                ->where('status', '!=', 'returned')
                ->join('clients', 'orders.client_id', '=', 'clients.id')
                ->join('client_types', 'orders.client_type_id', '=', 'client_types.id')
                ->where('orders.department_id', $department->department_id)
                ->select('orders.*', 'clients.name as client_name', 'client_types.name as client_type_name')
                ->get();

            $orderProducts = $orders->map(function ($order) {
                $products = OrderProduct::join('products', 'order_products.product_id', '=', 'products.id')
                    ->where('order_products.order_id', $order->id)
                    ->select('products.name', 'order_products.quantity', 'order_products.price')
                    ->get();
                $order->products = $products;

                return $order;
            });

            $department->orders = $orders;

            return $department;
        });

        return $departmentsWithOrders;
    }

    public function paymentReport($data)
    {
        $reports = Order::when(isset($data['from']), fn($query) => $query
            ->where('order_date', '>=', $data['from']))
            ->when(isset($data['to']), fn($query) => $query
                ->where('order_date', '<=', Carbon::parse($data['to'])))
            ->when(isset($data['departments']) && count($data['departments']) > 0, fn($query) => $query
                ->whereIn('department_id', $data['departments']))
            ->selectRaw('
                COALESCE(SUM(orders.tax), 0) as total_tax,
                COALESCE(SUM(orders.discount), 0) as total_discount,
                COALESCE(SUM(orders.total_price), 0) as total_price')
            // ->join('clients', 'orders.client_id', '=', 'clients.id')
            ->first();

        $costReport = Order::when(isset($data['from']), fn($query) => $query
            ->where('order_date', '>=', $data['from']))
            ->when(isset($data['to']), fn($query) => $query
                ->where('order_date', '<=', $data['to']))
            ->when(isset($data['departments']) && count($data['departments']) > 0, fn($query) => $query
                ->whereIn('department_id', $data['departments']))
            ->selectRaw('
                    COALESCE(SUM(order_products.cost_price), 0) as total_cost_price')
            ->join('order_products', 'order_products.order_id', '=', 'orders.id')
            ->first();

        $clients = Order::when(isset($data['from']), fn($query) => $query
            ->where('order_date', '>=', $data['from']))
            ->when(isset($data['to']), fn($query) => $query
                ->where('order_date', '<=', $data['to']))
            ->when(isset($data['departments']) && count($data['departments']) > 0, fn($query) => $query
                ->whereIn('department_id', $data['departments']))
            ->join('clients', 'orders.client_id', '=', 'clients.id')
            ->join('client_types', 'orders.client_type_id', '=', 'client_types.id')
            ->where('orders.discount', '!=', 0)
            ->selectRaw('
                COALESCE(SUM(orders.discount), 0) as discount,
                clients.name as client_name, 
                client_types.name as client_type_name')
            ->groupBy('clients.id', 'client_types.id')
            ->get();

        // $reports['clients'] =  $clients ;
        // return $reports ;
        return [
            'total_tax' => $reports->total_tax,
            'total_discount' => $reports->total_discount,
            'total_price' => $reports->total_price,
            'total_cost_price' => $costReport->total_cost_price,
            'clients' => $clients,
        ];
    }

    public function getDeoartmentProductsReport($data)
    {
        $orders = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->join('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->when(isset($data) && $data['department_id'], fn($query) => $query
                ->where('orders.department_id', $data['department_id']))
            ->when(isset($data) && isset($data['sub_category_id']) && $data['sub_category_id'], fn($query) => $query
                ->where('products.sub_category_id', $data['sub_category_id']))
            ->when(isset($data) && $data['category_id'], fn($query) => $query
                ->where('products.category_id', $data['category_id']))
            ->when(isset($data) && $data['name'], fn($query) => $query
                ->whereRaw('products.name like ?', ['%' . $data['name'] . '%']))
            ->when(isset($data) && $data['from'], fn($query) => $query
                ->where('orders.created_at', '>=', $data['from']))
            ->when(isset($data) && $data['to'], fn($query) => $query
                ->where('orders.created_at', '<=', Carbon::parse($data['to'])->endOfDay()))
            ->select(
                'order_products.product_id',
                'products.name',
                'categories.id as category_id',
                'categories.name as category_name',
                'sub_categories.id as sub_category_id',
                'sub_categories.name as sub_category_name',
                DB::raw('SUM(order_products.quantity) as total_quantity'),
                DB::raw('SUM(order_products.price) as price'),
            )
            ->distinct()
            ->groupBy('order_products.product_id');

        return $orders;
    }
}
