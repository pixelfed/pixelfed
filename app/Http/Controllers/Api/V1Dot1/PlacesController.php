<?php

namespace App\Http\Controllers\Api\V1Dot1;

use App\Http\Controllers\Controller;
use App\Place;
use App\Services\BouncerService;
use App\Services\StatusService;
use App\Status;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class PlacesController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function error($msg, $code = 400, $extra = [], $headers = [])
    {
        $res = [
            'msg' => $msg,
            'code' => $code,
        ];

        return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function placesById(Request $request, $id, $slug)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $place = Place::whereSlug($slug)->findOrFail($id);

        $posts = Cache::remember('pf-api:v1.1:places-by-id:'.$place->id, 3600, function () use ($place) {
            return Status::wherePlaceId($place->id)
                ->whereNull('uri')
                ->whereScope('public')
                ->orderByDesc('created_at')
                ->limit(60)
                ->pluck('id');
        });

        $posts = $posts->map(function ($id) {
            return StatusService::get($id);
        })
            ->filter()
            ->values();

        return [
            'place' => [
                'id' => $place->id,
                'name' => $place->name,
                'slug' => $place->slug,
                'country' => $place->country,
                'lat' => $place->lat,
                'long' => $place->long,
            ],
            'posts' => $posts];
    }
}