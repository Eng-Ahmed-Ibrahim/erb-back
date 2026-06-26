<?php

namespace App\Repositories\Invoice\Eloquent;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\RecipeQuantity;
use App\Repositories\Invoice\InvoiceRepository;
use App\Repositories\RecipeQuantity\RecipeQuantityRepository;
use App\Repositories\EloquentBaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EloquentInvoiceRepository extends EloquentBaseRepository implements InvoiceRepository
{
    private $recipeQuantitiesRepository;

    public function __construct()
    {
        parent::__construct(new Invoice);
        $this->recipeQuantitiesRepository = app(RecipeQuantityRepository::class);
    }

    public function adminCreate($data)
    {
        if (isset($data['image']) && $data['image']) {
            $data['image'] = $this->saveImage($data['image'], 'invoices_images');
        }
        $data['created_by'] = auth()->user()->id;


        $invoice = $this->model->create($data);
        foreach ($data['recipes'] as $recipePivot) {
            $invoice->recipes()->attach($recipePivot['recipe_id'], [
                'price' => $recipePivot['price'],
                'quantity' => $recipePivot['quantity'],
                'expire_date' => $recipePivot['expire_date'],
                'total_price' => $recipePivot['price'] * $recipePivot['quantity'],
                'source_invoice_id' => $recipePivot['invoice_id'] ?? null,
            ]);
        }

        return $invoice;
    }

    public function adminUpdate($invoice, $data)
    {
        $invoice = $invoice->update($data);

        return $invoice;
    }


    public function adminDelete($invoice)
    {
        if ($invoice->image) {
            Storage::disk('public')->delete($invoice->image);
        }
        $invoice->recipes()->detach();
        $invoice->delete();
    }

    public function reviewInvoice($model)
    {
        $model->status = 'approved';
        $model->save();

        return $model;
    }

    public function moveInvoiceToDepartment($data)
    {
        $fromDepartment = Department::findOrFail($data['from']);
        $toDepartment = Department::findOrFail($data['to']);
        $invoice = $this->findByAttributes(['code' => $data['code']]);

        if ($invoice->is_closed == false) {
            $invoice->update([
                'is_closed' => true,
            ]);
        } else {
            return [];
        }

        foreach ($invoice->recipes as $recipe) {
            $quantity = $recipe->pivot->quantity;
            $this->UpdateRecipeQuantites($fromDepartment, $toDepartment, $recipe, $quantity, false, $invoice->id);

            $this->createOrUpdatePivot($fromDepartment, $recipe);
            $this->createOrUpdatePivot($toDepartment, $recipe);
        }
        $data = $this->prepareData($invoice, $data);
        $invoice = $this->adminCreate($data);

        return $invoice;
    }

    private function prepareData($invoice, $data)
    {
        $recipes = [];

        foreach ($invoice->recipes as $recipe) {
            $recipes[] = [
                'recipe_id' => $recipe->id,
                'quantity' => $recipe->pivot->quantity,
                'price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
            ];
        }

        $data = [
            'from' => $data['from'],
            'to' => $data['to'],
            'invoice_date' => now()->format('Y-m-d'),
            'code' => $data['code'] ?? $this->generateCode($invoice),
            'status' => 'pending',
            'type' => 'out_going',
            'invoice_price' => $invoice->invoice_price,
            'discount' => $invoice->discount,
            'tax' => $invoice->tax,
            'total_price' => $invoice->total_price,
            'recipes' => $recipes,
        ];

        return $data;
    }

    public function generateCode($model)
    {
        do {
            $code = 'INV-' . rand(100000000, 999999999);
        } while ($model->where('code', $code)->exists());

        return $code;
    }

    protected function getPivotId($department, $recipe)
    {
        return DB::table('department_store')->select('id')->where('recipe_id', '=', $recipe->id)->where('department_id', '=', $department->id)->first()->id;
    }

    public function UpdateRecipeQuantites($fromDepartment, $toDepartment, $recipe, $quantity, $isReturned = false, $invoiceId = null)
    {
        $fromPivotId = $this->getPivotId($fromDepartment, $recipe);

        if (!$isReturned) {
            $this->createPivotIfNotExist($toDepartment, $recipe);
        }

        $quantites = RecipeQuantity::where('department_store_id', $fromPivotId)
            ->where('remaining', '>', 0)
            ->when(isset($invoiceId), fn($query) => $query
                ->where('invoice_id', $invoiceId))
            ->orderBy('expire_date', 'asc')
            ->get();

        foreach ($quantites as $recipeQuantity) {
            $remaining = $recipeQuantity->remaining;
            $invoiceId = $recipeQuantity->invoice_id;

            if ($remaining >= $quantity) {
                $recipeQuantity->remaining = $remaining - $quantity;
                $recipeQuantity->total_price = $recipeQuantity->remaining * $recipeQuantity->price;
                $recipeQuantity->save();

                if (!$isReturned) {
                    $this->createOrUpdateRecipeQuantity($toDepartment, $recipe, $invoiceId);
                }

                if ($recipeQuantity->remaining == 0) {
                    $recipeQuantity->delete();
                }
                break;
            } else {
                $recipeQuantity->remaining = 0;
                $recipeQuantity->total_price = $recipeQuantity->remaining * $recipeQuantity->price;
                $recipeQuantity->save();
                $quantity -= $remaining;
                $recipeQuantity->delete();
            }
        }
    }

    protected function createOrUpdateRecipeQuantity($department, $recipe, $invoice_id)
    {
        $pivotId = $this->getPivotId($department, $recipe);

        $existQuantity = $this->recipeQuantitiesRepository->findByAttributes([
            'department_store_id' => $pivotId,
            'recipe_id' => $recipe->id,
            'expire_date' => $recipe->pivot->expire_date,
            'price' => $recipe->pivot->price,
        ]);

        if ($existQuantity) {
            $existQuantity->update([
                'quantity' => $existQuantity->quantity + $recipe->pivot->quantity,
                'total_price' => $existQuantity->total_price + $recipe->pivot->price * $recipe->pivot->quantity,
                'remaining' => $existQuantity->remaining + $recipe->pivot->quantity,
            ]);

            return true;
        }

        return $this->recipeQuantitiesRepository->create([
            'recipe_id' => $recipe->id,
            'expire_date' => $recipe->pivot->expire_date,
            'price' => $recipe->pivot->price,
            'quantity' => $recipe->pivot->quantity,
            'remaining' => $recipe->pivot->quantity,
            'invoice_id' => $invoice_id,
            'total_price' => $recipe->pivot->price * $recipe->pivot->quantity,
            'department_store_id' => $pivotId,
        ]);
    }

    public function createOrUpdatePivot($department, $recipe)
    {
        $this->createPivotIfNotExist($department, $recipe);

        $pivotId = $this->getPivotId($department, $recipe);
        $quantities = $this->recipeQuantitiesRepository->getByAttributes(['department_store_id' => $pivotId])->where('remaining', '>', 0);
        $Price = $quantities->sum('total_price');
        $remaining = $quantities->sum('remaining');

        $department->recipes()->updateExistingPivot($recipe->id, [
            'quantity' => $remaining,
            'price' => $Price,
        ]);
    }

    protected function createPivotIfNotExist($department, $recipe)
    {
        $pivot = $department->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot;
        if (!$pivot) {
            $department->recipes()->attach($recipe->id, [
                'quantity' => $recipe->pivot->quantity,
                'price' => $recipe->pivot->price * $recipe->pivot->quantity,
            ]);

            return $department->recipes()->wherePivot('recipe_id', $recipe->id)->first()?->pivot;
        }

        return $pivot;
    }
}
