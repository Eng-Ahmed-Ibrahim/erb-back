<?php

namespace App\Service\Reports\RecipeReports;

use App\Repositories\Department\DepartmentRepository;
use App\Repositories\Recipe\RecipeRepository;
use App\Repositories\RecipeQuantity\RecipeQuantityRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecipeExpireLimitService
{
    private $recipeRepository;

    private $recipeQuantityRepository;

    private $departmentRepository;

    public function __construct()
    {
        $this->recipeRepository = app(RecipeRepository::class);
        $this->recipeQuantityRepository = app(RecipeQuantityRepository::class);
        $this->departmentRepository = app(DepartmentRepository::class);
    }

    public function getRecipesHasExpireDateBeforeDays($department_id)
    {

        $department = $this->departmentRepository->find($department_id);
        $recipes = $department->recipes()->wherePivot('quantity', '>', 0)->get();
        $today = Carbon::now();
        $formatedRecipes = [];
        foreach ($recipes as $index => $recipe) {
            $pivotId = DB::table('department_store')->select('id')
                ->where('recipe_id', $recipe->id)
                ->where('department_id', $department_id)
                ->first();
            if (! empty($pivotId)) {
                $quantites = $this->recipeQuantityRepository->getByAttributes(['department_store_id' => $pivotId->id]);
                $recipeToArray = $recipe;
                $quantitesDetail = [];
                foreach ($quantites as $quantity) {
                    $recipeToArray['unit'] = $recipe->unit;
                    $recipeToArray['recipe_category'] = $recipe->recipeCategory;
                    unset($recipeToArray->pivot);
                    $expireDate = Carbon::parse($quantity->expire_date);
                    $diffInDays = $expireDate->diffInDays($today);
                    $quantityDetail = [];
                    if ($diffInDays < $recipe->days_before_expire) {
                        $formatedRecipes[$index] = $recipeToArray->toArray();
                        $quantityDetail['quantity'] = $quantity->remaining;
                        $quantityDetail['expire_date'] = $quantity->expire_date;
                        $quantityDetail['invoice_id'] = $quantity->invoice_id;
                        $quantityDetail['price'] = $quantity->price;
                        $quantityDetail['total_price'] = $quantity->price * $quantity->remaining;
                    }
                    if (count($quantityDetail) > 0) {
                        $recipeToArray = $recipe;
                        $formatedRecipes[$index] = $recipeToArray->toArray();
                        $quantitesDetail[] = $quantityDetail ?? [];
                        if (count($quantitesDetail) > 0) {
                            $formatedRecipes[$index]['quantities'] = $quantitesDetail;
                        }
                    }
                }

            }
        }

        return $formatedRecipes;
    }
}
