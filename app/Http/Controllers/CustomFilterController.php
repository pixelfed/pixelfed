<?php

namespace App\Http\Controllers;

use App\Models\CustomFilter;
use App\Models\CustomFilterKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CustomFilterController extends Controller
{
    // const ACTIVE_TYPES = ['home', 'public', 'tags', 'notifications', 'thread', 'profile', 'groups'];
    const ACTIVE_TYPES = ['home', 'public', 'tags'];

    public function index(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $filters = CustomFilter::where('profile_id', $request->user()->profile_id)
            ->unexpired()
            ->with(['keywords'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($filter) {
                return [
                    'id' => $filter->id,
                    'title' => $filter->title,
                    'context' => $filter->context,
                    'expires_at' => $filter->expires_at,
                    'filter_action' => $filter->filterAction,
                    'keywords' => $filter->keywords->map(function ($keyword) {
                        return [
                            'id' => $keyword->id,
                            'keyword' => $keyword->keyword,
                            'whole_word' => (bool) $keyword->whole_word,
                        ];
                    }),
                    'statuses' => [],
                ];
            });

        return response()->json($filters);
    }

    public function show(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $filter = CustomFilter::findOrFail($id);
        Gate::authorize('view', $filter);

        $filter->load(['keywords']);

        $res = [
            'id' => $filter->id,
            'title' => $filter->title,
            'context' => $filter->context,
            'expires_at' => $filter->expires_at,
            'filter_action' => $filter->filterAction,
            'keywords' => $filter->keywords->map(function ($keyword) {
                return [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'whole_word' => (bool) $keyword->whole_word,
                ];
            }),
            'statuses' => [],
        ];

        return response()->json($res);
    }

    public function store(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        Gate::authorize('create', CustomFilter::class);

        $validatedData = $request->validate([
            'title' => 'required|string|max:100',
            'context' => 'required|array',
            'context.*' => [
                'string',
                Rule::in(self::ACTIVE_TYPES),
            ],
            'filter_action' => 'string|in:warn,hide,blur',
            'expires_in' => 'nullable|integer|min:0|max:63072000',
            'keywords_attributes' => 'required|array|min:1|max:'.CustomFilter::getMaxKeywordsPerFilter(),
            'keywords_attributes.*.keyword' => [
                'required',
                'string',
                'min:1',
                'max:'.CustomFilter::getMaxKeywordLength(),
                'regex:/^[\p{L}\p{N}\p{Zs}\p{P}\p{M}]+$/u',
                function ($attribute, $value, $fail) {
                    if (preg_match('/(.)\1{20,}/', $value)) {
                        $fail('The keyword contains excessive character repetition.');
                    }
                },
            ],
            'keywords_attributes.*.whole_word' => 'boolean',
        ]);
        $profile_id = $request->user()->profile_id;
        $userFilterCount = CustomFilter::where('profile_id', $profile_id)->count();
        $maxFiltersPerUser = CustomFilter::getMaxFiltersPerUser();

        if (! $request->user()->is_admin && $userFilterCount >= $maxFiltersPerUser) {
            return response()->json([
                'error' => 'Filter limit exceeded',
                'message' => 'You can only have '.$maxFiltersPerUser.' filters at a time.',
            ], 422);
        }

        $rateKey = 'filters_created:'.$request->user()->id;
        $maxFiltersPerHour = CustomFilter::getMaxCreatePerHour();
        $currentCount = Cache::get($rateKey, 0);

        if (! $request->user()->is_admin && $currentCount >= $maxFiltersPerHour) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'You can only create '.$maxFiltersPerHour.' filters per hour.',
            ], 429);
        }

        DB::beginTransaction();

        try {

            $requestedKeywords = array_map(function ($item) {
                return mb_strtolower(trim($item['keyword']));
            }, $validatedData['keywords_attributes']);

            $existingKeywords = DB::table('custom_filter_keywords')
                ->join('custom_filters', 'custom_filter_keywords.custom_filter_id', '=', 'custom_filters.id')
                ->where('custom_filters.profile_id', $profile_id)
                ->whereIn('custom_filter_keywords.keyword', $requestedKeywords)
                ->pluck('custom_filter_keywords.keyword')
                ->toArray();

            if (! empty($existingKeywords)) {
                return response()->json([
                    'error' => 'Duplicate keywords found',
                    'message' => 'The following keywords already exist: '.implode(', ', $existingKeywords),
                ], 422);
            }

            $expiresAt = null;
            if (isset($validatedData['expires_in']) && $validatedData['expires_in'] > 0) {
                $expiresAt = now()->addSeconds($validatedData['expires_in']);
            }

            $action = CustomFilter::ACTION_WARN;
            if (isset($validatedData['filter_action'])) {
                $action = $this->filterActionToAction($validatedData['filter_action']);
            }

            $filter = CustomFilter::create([
                'title' => $validatedData['title'],
                'context' => $validatedData['context'],
                'action' => $action,
                'expires_at' => $expiresAt,
                'profile_id' => $request->user()->profile_id,
            ]);

            if (isset($validatedData['keywords_attributes'])) {
                foreach ($validatedData['keywords_attributes'] as $keywordData) {
                    $keyword = trim($keywordData['keyword']);

                    $filter->keywords()->create([
                        'keyword' => $keyword,
                        'whole_word' => (bool) $keywordData['whole_word'] ?? true,
                    ]);
                }
            }

            Cache::increment($rateKey);
            if (! Cache::has($rateKey)) {
                Cache::put($rateKey, 1, 3600);
            }

            Cache::forget("filters:v3:{$profile_id}");

            DB::commit();

            $filter->load(['keywords', 'statuses']);

            $res = [
                'id' => $filter->id,
                'title' => $filter->title,
                'context' => $filter->context,
                'expires_at' => $filter->expires_at,
                'filter_action' => $filter->filterAction,
                'keywords' => $filter->keywords->map(function ($keyword) {
                    return [
                        'id' => $keyword->id,
                        'keyword' => $keyword->keyword,
                        'whole_word' => (bool) $keyword->whole_word,
                    ];
                }),
                'statuses' => $filter->statuses->map(function ($status) {
                    return [
                        'id' => $status->id,
                        'status_id' => $status->status_id,
                    ];
                }),
            ];

            return response()->json($res, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create filter',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert Mastodon filter_action string to internal action value
     *
     * @param  string  $filterAction
     * @return int
     */
    private function filterActionToAction($filterAction)
    {
        switch ($filterAction) {
            case 'warn':
                return CustomFilter::ACTION_WARN;
            case 'hide':
                return CustomFilter::ACTION_HIDE;
            case 'blur':
                return CustomFilter::ACTION_BLUR;
            default:
                return CustomFilter::ACTION_WARN;
        }
    }

    public function update(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $filter = CustomFilter::findOrFail($id);
        $pid = $request->user()->profile_id;
        if ($filter->profile_id !== $pid) {
            return response()->json(['error' => 'This action is unauthorized'], 401);
        }
        Gate::authorize('update', $filter);

        $validatedData = $request->validate([
            'title' => 'string|max:100',
            'context' => 'array|max:10',
            'context.*' => [
                'string',
                Rule::in(self::ACTIVE_TYPES),
            ],
            'filter_action' => 'string|in:warn,hide,blur',
            'expires_in' => 'nullable|integer|min:0|max:63072000',
            'keywords_attributes' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $activeKeywords = collect($value)->filter(function ($keyword) {
                        return ! isset($keyword['_destroy']) || $keyword['_destroy'] !== true;
                    })->count();

                    if ($activeKeywords > CustomFilter::getMaxKeywordsPerFilter()) {
                        $fail('You may not have more than '.CustomFilter::getMaxKeywordsPerFilter().' active keywords.');
                    }
                },
            ],
            'keywords_attributes.*.id' => 'nullable|integer|exists:custom_filter_keywords,id',
            'keywords_attributes.*.keyword' => [
                'required_without:keywords_attributes.*.id',
                'string',
                'min:1',
                'max:'.CustomFilter::getMaxKeywordLength(),
                'regex:/^[\p{L}\p{N}\p{Zs}\p{P}\p{M}]+$/u',
                function ($attribute, $value, $fail) {
                    if (preg_match('/(.)\1{20,}/', $value)) {
                        $fail('The keyword contains excessive character repetition.');
                    }
                },
            ],
            'keywords_attributes.*.whole_word' => 'boolean',
            'keywords_attributes.*._destroy' => 'boolean',
        ]);

        $rateKey = 'filters_updated:'.$request->user()->id;
        $maxUpdatesPerHour = CustomFilter::getMaxUpdatesPerHour();
        $currentCount = Cache::get($rateKey, 0);

        if (! $request->user()->is_admin && $currentCount >= $maxUpdatesPerHour) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'You can only update filters '.$maxUpdatesPerHour.' times per hour.',
            ], 429);
        }

        DB::beginTransaction();

        try {

            $keywordIds = collect($validatedData['keywords_attributes'])->pluck('id')->filter()->toArray();
            if (count($keywordIds) && ! CustomFilterKeyword::whereCustomFilterId($filter->id)->whereIn('id', $keywordIds)->count()) {
                return response()->json([
                    'error' => 'Record not found',
                ], 404);
            }

            $requestedKeywords = [];
            foreach ($validatedData['keywords_attributes'] as $item) {
                if (isset($item['keyword']) && (! isset($item['_destroy']) || ! $item['_destroy'])) {
                    $requestedKeywords[] = mb_strtolower(trim($item['keyword']));
                }
            }

            if (! empty($requestedKeywords)) {
                $existingKeywords = DB::table('custom_filter_keywords')
                    ->join('custom_filters', 'custom_filter_keywords.custom_filter_id', '=', 'custom_filters.id')
                    ->where('custom_filters.profile_id', $pid)
                    ->whereIn('custom_filter_keywords.keyword', $requestedKeywords)
                    ->where('custom_filter_keywords.custom_filter_id', '!=', $id)
                    ->pluck('custom_filter_keywords.keyword')
                    ->toArray();

                if (! empty($existingKeywords)) {
                    return response()->json([
                        'error' => 'Duplicate keywords found',
                        'message' => 'The following keywords already exist: '.implode(', ', $existingKeywords),
                    ], 422);
                }
            }

            if (isset($validatedData['expires_in'])) {
                if ($validatedData['expires_in'] > 0) {
                    $filter->expires_at = now()->addSeconds($validatedData['expires_in']);
                } else {
                    $filter->expires_at = null;
                }
            }

            if (isset($validatedData['title'])) {
                $filter->title = $validatedData['title'];
            }

            if (isset($validatedData['context'])) {
                $filter->context = $validatedData['context'];
            }

            if (isset($validatedData['filter_action'])) {
                $filter->action = $this->filterActionToAction($validatedData['filter_action']);
            }

            $filter->save();

            if (isset($validatedData['keywords_attributes'])) {
                $existingKeywords = $filter->keywords()->pluck('id')->toArray();

                $processedIds = [];

                foreach ($validatedData['keywords_attributes'] as $keywordData) {
                    // Case 1: Explicit deletion with _destroy flag
                    if (isset($keywordData['id']) && isset($keywordData['_destroy']) && (bool) $keywordData['_destroy']) {
                        // Verify this ID belongs to this filter before deletion
                        $kwf = CustomFilterKeyword::where('custom_filter_id', $filter->id)
                            ->where('id', $keywordData['id'])
                            ->first();

                        if ($kwf) {
                            $kwf->delete();
                            $processedIds[] = $keywordData['id'];
                        }
                    }
                    // Case 2: Update existing keyword
                    elseif (isset($keywordData['id'])) {
                        // Skip if we've already processed this ID
                        if (in_array($keywordData['id'], $processedIds)) {
                            continue;
                        }

                        // Verify this ID belongs to this filter before updating
                        $keyword = CustomFilterKeyword::where('custom_filter_id', $filter->id)
                            ->where('id', $keywordData['id'])
                            ->first();

                        if (! isset($keywordData['_destroy']) && $filter->keywords()->pluck('id')->search($keywordData['id']) === false) {
                            return response()->json([
                                'error' => 'Duplicate keywords found',
                                'message' => 'The following keywords already exist: '.$keywordData['keyword'],
                            ], 422);
                        }

                        if ($keyword) {
                            $updateData = [];

                            if (isset($keywordData['keyword'])) {
                                $updateData['keyword'] = trim($keywordData['keyword']);
                            }

                            if (isset($keywordData['whole_word'])) {
                                $updateData['whole_word'] = (bool) $keywordData['whole_word'];
                            }

                            if (! empty($updateData)) {
                                $keyword->update($updateData);
                            }

                            $processedIds[] = $keywordData['id'];
                        }
                    }
                    // Case 3: Create new keyword
                    elseif (isset($keywordData['keyword'])) {
                        // Check if we're about to exceed the keyword limit
                        $existingKeywordCount = $filter->keywords()->count();
                        $maxKeywordsPerFilter = CustomFilter::getMaxKeywordsPerFilter();

                        if ($existingKeywordCount >= $maxKeywordsPerFilter) {
                            return response()->json([
                                'error' => 'Keyword limit exceeded',
                                'message' => 'A filter can have a maximum of '.$maxKeywordsPerFilter.' keywords.',
                            ], 422);
                        }

                        // Skip existing case-insensitive keywords
                        if ($filter->keywords()->pluck('keyword')->search(mb_strtolower(trim($keywordData['keyword']))) !== false) {
                            continue;
                        }

                        $filter->keywords()->create([
                            'keyword' => trim($keywordData['keyword']),
                            'whole_word' => (bool) ($keywordData['whole_word'] ?? true),
                        ]);
                    }
                }
            }

            Cache::increment($rateKey);
            if (! Cache::has($rateKey)) {
                Cache::put($rateKey, 1, 3600);
            }

            Cache::forget("filters:v3:{$pid}");

            DB::commit();

            $filter->load(['keywords', 'statuses']);

            $res = [
                'id' => $filter->id,
                'title' => $filter->title,
                'context' => $filter->context,
                'expires_at' => $filter->expires_at,
                'filter_action' => $filter->filterAction,
                'keywords' => $filter->keywords->map(function ($keyword) {
                    return [
                        'id' => $keyword->id,
                        'keyword' => $keyword->keyword,
                        'whole_word' => (bool) $keyword->whole_word,
                    ];
                }),
                'statuses' => $filter->statuses->map(function ($status) {
                    return [
                        'id' => $status->id,
                        'status_id' => $status->status_id,
                    ];
                }),
            ];

            return response()->json($res);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update filter',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $filter = CustomFilter::findOrFail($id);
        Gate::authorize('delete', $filter);
        $filter->delete();

        return response()->json((object) [], 200);
    }
}
