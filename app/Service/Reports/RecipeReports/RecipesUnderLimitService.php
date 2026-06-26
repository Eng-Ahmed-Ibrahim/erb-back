<?php

namespace App\Service\Reports\RecipeReports;

use App\Models\DepartmentStore;
use App\Models\Recipe;
use App\Repositories\Recipe\RecipeRepository;
use App\Repositories\RecipeQuantity\RecipeQuantityRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecipesUnderLimitService
{
    private $recipeRepository;

    private $recipeQuantityRepository;

    public function __construct()
    {
        $this->recipeRepository = app(RecipeRepository::class);
        $this->recipeQuantityRepository = app(RecipeQuantityRepository::class);
    }

    public function getRecipesUnderLimit($data)
    {
        $recipes = DB::select('
                SELECT 
                    r.*,
                    ds.quantity as department_store_quantity,
                    u.name as unit_name,
                    rc.name as recipe_category_name,
                    rpc.name as recipe_parent_name
                from 
                    department_store ds 
                join recipes r  
                    on r.id = ds.recipe_id
                join units u
                    on u.id = r.unit_id
                join recipe_categories rc
                    on rc.id = r.recipe_category_id
                join  recipe_parent_categories rpc
                    on rpc.id = rc.category_id
                where
                    ds.department_id = ? 
                    AND 
                    ds.quantity <= r.minimum_limt
                    AND 
                    (? IS NULL OR R.NAME LIKE  ?)
                    AND 
                    (? IS NULL OR RPC.ID =  ?)',
            [$data['department_id'], $data['name'] ?? null, '%' . ($data['name'] ?? '') . '%', $data['category_id'] ?? null, $data['category_id'] ?? null]);

        return $recipes;
    }
}
