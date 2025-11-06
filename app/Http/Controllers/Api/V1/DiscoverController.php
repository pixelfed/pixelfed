<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DiscoverService;
use App\Services\StatusService;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class DiscoverController extends Controller
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

    /**
     * GET /api/v1/discover/posts
     *
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function discoverPosts(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
        ]);

        $limit = $request->input('limit') ?? 20;
        $posts = DiscoverService::getForYou();

        if (empty($posts)) {
            return $this->json([]);
        }

        $res = collect($posts)
            ->take($limit)
            ->map(function ($id) {
                return StatusService::getMastodon($id, false);
            })
            ->filter()
            ->values();

        return $this->json($res);
    }

    /**
     * GET /api/v1/discover/accounts/popular
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function discoverAccountsPopular(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $accounts = DiscoverService::getPopularAccounts();

        return $this->json($accounts);
    }
}