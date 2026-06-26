<?php

namespace App\Transformers\Recipe;

use App\Models\Recipe;
use App\Transformers\Product\AbstractProductTransformer;
use Flugg\Responder\Transformers\Transformer;

class RecipeProductsTransformer extends Transformer
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
     * @return array
     */
    public function transform(Recipe $recipe)
    {
        $formatedProducts = [];
        foreach ($recipe->products()->get() as $product) {

            $formatedProducts[] = AbstractProductTransformer::transform($product);
        }

        return [
            'id' => (int) $recipe->id,
            'name' => $recipe->name,
            'products' => $formatedProducts,
        ];
    }
}
