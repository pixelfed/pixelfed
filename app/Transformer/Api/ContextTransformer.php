<?php

namespace App\Transformer\Api;

use League\Fractal;

class ContextTransformer extends Fractal\TransformerAbstract
{
    public function transform()
    {
        return [
            'ancestors' => [],
            'descendants' => []
        ];
    }
}
