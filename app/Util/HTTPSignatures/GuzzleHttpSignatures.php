<?php

namespace App\Util\HttpSignatures;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use App\Util\HttpSignatures\Context;

class GuzzleHttpSignatures
{
    /**
     * @param Context $context
     * @return HandlerStack
     */
    public static function defaultHandlerFromContext(Context $context)
    {
        $stack = HandlerStack::create();
        $stack->push(self::middlewareFromContext($context));

        return $stack;
    }

    /**
     * @param Context $context
     * @return \Closure
     */
    public static function middlewareFromContext(Context $context)
    {
        return function (callable $handler) use ($context)
        {
            return function (
                Request $request,
                array $options
            ) use ($handler, $context)
            {
                $request = $context->signer()->sign($request);
                return $handler($request, $options);
            };
        };
    }
}