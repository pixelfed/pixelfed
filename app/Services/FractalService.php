<?php

namespace App\Services;

use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\TransformerAbstract;

class FractalService
{
    /**
     * Transform a single item using Fractal with ArraySerializer.
     *
     * @param  mixed  $item  The model or data to transform
     * @param  TransformerAbstract  $transformer  The transformer instance
     */
    public static function item($item, TransformerAbstract $transformer): array
    {
        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($item, $transformer);

        return $fractal->createData($resource)->toArray();
    }

    /**
     * Transform a collection using Fractal with ArraySerializer.
     *
     * @param  mixed  $collection  The collection or array to transform
     * @param  TransformerAbstract  $transformer  The transformer instance
     */
    public static function collection($collection, TransformerAbstract $transformer): array
    {
        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Collection($collection, $transformer);

        return $fractal->createData($resource)->toArray();
    }
}
