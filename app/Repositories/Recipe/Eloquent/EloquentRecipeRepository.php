<?php

namespace App\Repositories\Recipe\Eloquent;

use App\Models\Invoice;
use App\Models\Recipe;  // Make sure this is included
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Recipe\RecipeRepository;
use Illuminate\Support\Facades\Storage;

class EloquentRecipeRepository extends EloquentBaseRepository implements RecipeRepository
{
    public function adminCreate($data)
    {
        if (isset($data['image'])) {
            $data['image'] = $this->saveImage($data['image'], 'recipes_images');
        }

        return $this->create($data);
    }

    public function adminUpdate($model, $data)
    {
        if (isset($data['image'])) {
            if ($data['image']) {
                Storage::disk('public')->delete($model->image);
                $data['image'] = $this->saveImage($data['image'], 'recipes_images');
            } else {
                unset($data['image']);
            }
        }

        return $this->update($model, $data);
    }

    public function adminDelete($model)
    {
        Storage::disk('public')->delete($model->image ?? 'random');

        return $this->delete($model);
    }

    public function findByCategory($recipe_category_id)
    {
        return Recipe::where('recipe_category_id', $recipe_category_id)->get();
    }

    public function getAllRecipeInvoices($data = [])
    {
        $invoicesWithRecipes = Invoice::with(['recipes', 'supplier'])
            ->get()
            ->map(function ($invoice) {
                return $invoice->recipes->map(function ($recipe) use ($invoice) {
                    return [
                        'recipe_name' => $recipe->name,
                        'recipe' => [
                            'name' => $recipe->name,
                            'id' => $recipe->id,
                        ],
                        'invoice_id' => $invoice->id,
                        'invoice_image' => (string) config('app.url').$invoice->image,
                        'invoice_date' => $invoice->invoice_date,
                        'invoice_code' => $invoice->code,
                        'type' => $invoice->type,
                        'to' => $invoice->to,
                        'from' => $invoice->from,
                        'supplier' => [
                            'name' => $invoice->supplier ? $invoice->supplier->name : 'NA',
                            'id' => $invoice->supplier ? $invoice->supplier->id : 'NA',
                        ],
                        'recipe_price' => $recipe->pivot->price,
                        'quantity' => $recipe->pivot->quantity,
                    ];
                });
            })
            ->flatten(1);

        return $invoicesWithRecipes;
    }
}
