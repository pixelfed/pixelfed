<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\SearchApiV2Service;
use App\Services\UserRoleService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    const PF_API_ENTITY_KEY = '_pe';

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v2/search
     *
     *
     * @return array
     */
    public function search(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'q' => 'required|string|min:1|max:100',
            'account_id' => 'nullable|string',
            'max_id' => 'nullable|string',
            'min_id' => 'nullable|string',
            'type' => 'nullable|in:accounts,hashtags,statuses',
            'exclude_unreviewed' => 'nullable',
            'resolve' => 'nullable',
            'limit' => 'nullable|integer|max:40',
            'offset' => 'nullable|integer',
            'following' => 'nullable',
        ]);

        if ($request->user()->has_roles && ! UserRoleService::can('can-view-discover', $request->user()->id)) {
            return [
                'accounts' => [],
                'hashtags' => [],
                'statuses' => [],
            ];
        }

        $mastodonMode = ! $request->has('_pe');

        return $this->json(SearchApiV2Service::query($request, $mastodonMode));
    }
}