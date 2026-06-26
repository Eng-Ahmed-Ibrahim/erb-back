<?php

namespace App\Transformers;

use Flugg\Responder\Transformers\Transformer;

class BaseTransformer extends Transformer
{
    public static function formatMany($items, $transformer)
    {
        $formatedData = [];
        foreach ($items as $item) {
            $formatedData[] = $transformer::transform($item);
        }

        return $formatedData;
    }
}
