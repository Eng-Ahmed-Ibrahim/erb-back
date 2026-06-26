<?php

namespace App\Transformers;

use App\Models\DepartmentInventoryReview;
use Carbon\Carbon;
use Flugg\Responder\Transformers\Transformer;

class DepartmentInventoryReviewTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @param  \App\Models\DepartmentInventoryReview $departmentInventoryReview
     * @return array
     */
    public function transform(DepartmentInventoryReview $departmentInventoryReview)
    {
        $formatedRecipes = [];
        $recipes = $departmentInventoryReview?->invoice?->recipes;
        foreach ($recipes as $recipe) {
            $formatedRecipes[] = [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'minimum_limt' => $recipe->minimum_limt,
                'quantity' => $recipe->pivot->quantity,
                'price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'total_price' => $recipe->pivot->total_price,
            ];
        }

        return [
            'id' => $departmentInventoryReview?->id,
            'department' => $departmentInventoryReview?->department,
            'department_name' => $departmentInventoryReview?->department?->name,
            'code' => $departmentInventoryReview?->invoice?->code,
            'cashier' => $departmentInventoryReview?->cashier,
            'cashier_name' => $departmentInventoryReview?->cashier?->name,
            'recipes' => $formatedRecipes,
            'waiter' => $departmentInventoryReview?->waiter,
            'waiter_name' => $departmentInventoryReview?->waiter?->name,
            'discrepancy_note' => $departmentInventoryReview?->discrepancy_note,
            'total_missing_quantity' => $departmentInventoryReview?->total_missing_quantity,
            'estimated_loss_amount' => $departmentInventoryReview?->estimated_loss_amount,
            'reviewed_by' => $departmentInventoryReview?->reviewer,
            'reviewed_at' => $departmentInventoryReview?->reviewed_at,
            'created_at' => Carbon::parse($departmentInventoryReview?->created_at)?->format('Y-m-d'),
            'status' => $departmentInventoryReview?->status,
        ];
    }
}
