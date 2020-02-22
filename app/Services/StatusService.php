<?php

namespace App\Services;

use Redis;
use App\Status;
use App\Transformer\Api\StatusStatelessTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class StatusService
{
    const CACHE_KEY = 'pf:services:status:';

    public static function get($id)
    {
        return Redis::get(self::CACHE_KEY . $id) ?? self::coldGet($id);
    }

    public static function coldGet($id)
    {
        $status = Status::findOrFail($id);
        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($status, new StatusStatelessTransformer());
        $res = $fractal->createData($resource)->toJson();
        self::set($id, $res);
        return $res;
    }

    public static function set($key, $val)
    {
        return Redis::set(self::CACHE_KEY . $key, $val);
    }

    public static function del($key)
    {
        return Redis::del(self::CACHE_KEY . $key);
    }

    public static function rem($key)
    {
        return self::del($key);
    }
}
