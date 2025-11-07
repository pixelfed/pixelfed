<?php

namespace App\Models;

use App\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CustomFilter extends Model
{
    public $shouldInvalidateCache = false;

    protected $fillable = [
        'title', 'phrase', 'context', 'expires_at', 'action', 'profile_id',
    ];

    protected $casts = [
        'id' => 'string',
        'context' => 'array',
        'expires_at' => 'datetime',
        'action' => 'integer',
    ];

    protected $guarded = ['shouldInvalidateCache'];

    const VALID_CONTEXTS = [
        'home',
        'notifications',
        'public',
        'thread',
        'account',
    ];

    const MAX_STATUSES_PER_FILTER = 10;

    const EXPIRATION_DURATIONS = [
        1800,   // 30 minutes
        3600,   // 1 hour
        21600,  // 6 hours
        43200,  // 12 hours
        86400,  // 1 day
        604800, // 1 week
    ];

    const ACTION_WARN = 0;

    const ACTION_HIDE = 1;

    const ACTION_BLUR = 2;

    protected static ?int $maxContentScanLimit = null;

    protected static ?int $maxFiltersPerUser = null;

    protected static ?int $maxKeywordsPerFilter = null;

    protected static ?int $maxKeywordsLength = null;

    protected static ?int $maxPatternLength = null;

    protected static ?int $maxCreatePerHour = null;

    protected static ?int $maxUpdatesPerHour = null;

    public function account()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function keywords()
    {
        return $this->hasMany(CustomFilterKeyword::class);
    }

    public function statuses()
    {
        return $this->hasMany(CustomFilterStatus::class);
    }

    public function toFilterArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'context' => $this->context,
            'expires_at' => $this->expires_at,
            'filter_action' => $this->filterAction,
        ];
    }

    public function getFilterActionAttribute()
    {
        switch ($this->action) {
            case 0:
                return 'warn';

            case 1:
                return 'hide';

            case 2:
                return 'blur';
        }
    }

    public function getTitleAttribute()
    {
        return $this->phrase;
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['phrase'] = $value;
    }

    public function setFilterActionAttribute($value)
    {
        $this->attributes['action'] = $value;
    }

    public function setIrreversibleAttribute($value)
    {
        $this->attributes['action'] = $value ? self::ACTION_HIDE : self::ACTION_WARN;
    }

    public function getIrreversibleAttribute()
    {
        return $this->action === self::ACTION_HIDE;
    }

    public function getExpiresInAttribute()
    {
        if ($this->expires_at === null) {
            return null;
        }

        $now = now();
        foreach (self::EXPIRATION_DURATIONS as $duration) {
            if ($now->addSeconds($duration)->gte($this->expires_at)) {
                return $duration;
            }
        }

        return null;
    }

    public function scopeUnexpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function isExpired()
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->prepareContextForStorage();
            $model->shouldInvalidateCache = true;
        });

        static::updating(function ($model) {
            $model->prepareContextForStorage();
            $model->shouldInvalidateCache = true;
        });

        static::deleting(function ($model) {
            $model->shouldInvalidateCache = true;
        });

        static::saved(function ($model) {
            $model->invalidateCache();
        });

        static::deleted(function ($model) {
            $model->invalidateCache();
        });
    }

    protected function prepareContextForStorage()
    {
        if (is_array($this->context)) {
            $this->context = array_values(array_filter(array_map('trim', $this->context)));
        }
    }

    protected function invalidateCache()
    {
        if (! isset($this->shouldInvalidateCache) || ! $this->shouldInvalidateCache) {
            return;
        }

        $this->shouldInvalidateCache = false;

        Cache::forget("filters:v3:{$this->profile_id}");
    }

    public static function getMaxContentScanLimit(): int
    {
        if (self::$maxContentScanLimit === null) {
            self::$maxContentScanLimit = config('instance.custom_filters.max_content_scan_limit', 2500);
        }

        return self::$maxContentScanLimit;
    }

    public static function getMaxFiltersPerUser(): int
    {
        if (self::$maxFiltersPerUser === null) {
            self::$maxFiltersPerUser = config('instance.custom_filters.max_filters_per_user', 20);
        }

        return self::$maxFiltersPerUser;
    }

    public static function getMaxKeywordsPerFilter(): int
    {
        if (self::$maxKeywordsPerFilter === null) {
            self::$maxKeywordsPerFilter = config('instance.custom_filters.max_keywords_per_filter', 10);
        }

        return self::$maxKeywordsPerFilter;
    }

    public static function getMaxKeywordLength(): int
    {
        if (self::$maxKeywordsLength === null) {
            self::$maxKeywordsLength = config('instance.custom_filters.max_keyword_length', 40);
        }

        return self::$maxKeywordsLength;
    }

    public static function getMaxPatternLength(): int
    {
        if (self::$maxPatternLength === null) {
            self::$maxPatternLength = config('instance.custom_filters.max_pattern_length', 10000);
        }

        return self::$maxPatternLength;
    }

    public static function getMaxCreatePerHour(): int
    {
        if (self::$maxCreatePerHour === null) {
            self::$maxCreatePerHour = config('instance.custom_filters.max_create_per_hour', 20);
        }

        return self::$maxCreatePerHour;
    }

    public static function getMaxUpdatesPerHour(): int
    {
        if (self::$maxUpdatesPerHour === null) {
            self::$maxUpdatesPerHour = config('instance.custom_filters.max_updates_per_hour', 40);
        }

        return self::$maxUpdatesPerHour;
    }

    /**
     * Get cached filters for an account with simplified, secure approach
     *
     * @param  int  $profileId  The profile ID
     * @return Collection The collection of filters
     */
    public static function getCachedFiltersForAccount($profileId)
    {
        $activeFilters = Cache::remember("filters:v3:{$profileId}", 3600, function () use ($profileId) {
            $filtersHash = [];

            $keywordFilters = CustomFilterKeyword::with(['customFilter' => function ($query) use ($profileId) {
                $query->unexpired()->where('profile_id', $profileId);
            }])->get();

            $keywordFilters->groupBy('custom_filter_id')->each(function ($keywords, $filterId) use (&$filtersHash) {
                $filter = $keywords->first()->customFilter;

                if (! $filter) {
                    return;
                }

                $maxPatternsPerFilter = self::getMaxFiltersPerUser();
                $keywordsToProcess = $keywords->take($maxPatternsPerFilter);

                $regexPatterns = $keywordsToProcess->map(function ($keyword) {
                    $pattern = preg_quote($keyword->keyword, '/');

                    if ($keyword->whole_word) {
                        $pattern = '\b'.$pattern.'\b';
                    }

                    return $pattern;
                })->toArray();

                if (empty($regexPatterns)) {
                    return;
                }

                $combinedPattern = implode('|', $regexPatterns);
                $maxPatternLength = self::getMaxPatternLength();
                if (strlen($combinedPattern) > $maxPatternLength) {
                    $combinedPattern = substr($combinedPattern, 0, $maxPatternLength);
                }

                $filtersHash[$filterId] = [
                    'keywords' => '/'.$combinedPattern.'/i',
                    'filter' => $filter,
                ];
            });

            // $statusFilters = CustomFilterStatus::with(['customFilter' => function ($query) use ($profileId) {
            //     $query->unexpired()->where('profile_id', $profileId);
            // }])->get();

            // $statusFilters->groupBy('custom_filter_id')->each(function ($statuses, $filterId) use (&$filtersHash) {
            //     $filter = $statuses->first()->customFilter;

            //     if (! $filter) {
            //         return;
            //     }

            //     if (! isset($filtersHash[$filterId])) {
            //         $filtersHash[$filterId] = ['filter' => $filter];
            //     }

            //     $maxStatusIds = self::MAX_STATUSES_PER_FILTER;
            //     $filtersHash[$filterId]['status_ids'] = $statuses->take($maxStatusIds)->pluck('status_id')->toArray();
            // });

            return array_map(function ($item) {
                $filter = $item['filter'];
                unset($item['filter']);

                return [$filter, $item];
            }, $filtersHash);
        });

        return collect($activeFilters)->reject(function ($item) {
            [$filter, $rules] = $item;

            return $filter->isExpired();
        })->toArray();
    }

    /**
     * Apply cached filters to a status with reasonable safety measures
     *
     * @param  array  $cachedFilters  The cached filters
     * @param  mixed  $status  The status to check
     * @return array The filter matches
     */
    public static function applyCachedFilters($cachedFilters, $status)
    {
        $results = [];

        foreach ($cachedFilters as [$filter, $rules]) {
            $keywordMatches = [];
            $statusMatches = null;

            if (isset($rules['keywords'])) {
                $text = strip_tags($status['content']);

                $maxContentLength = self::getMaxContentScanLimit();
                if (mb_strlen($text) > $maxContentLength) {
                    $text = mb_substr($text, 0, $maxContentLength);
                }

                try {
                    preg_match_all($rules['keywords'], $text, $matches, PREG_PATTERN_ORDER, 0);
                    if (! empty($matches[0])) {
                        $maxReportedMatches = (int) config('instance.custom_filters.max_reported_matches', 10);
                        $keywordMatches = array_slice($matches[0], 0, $maxReportedMatches);
                    }
                } catch (\Throwable $e) {
                    \Log::error('Filter regex error: '.$e->getMessage(), [
                        'filter_id' => $filter->id,
                    ]);
                }
            }

            // if (isset($rules['status_ids'])) {
            //     $statusId = $status->id;
            //     $reblogId = $status->reblog_of_id ?? null;

            //     $matchingIds = array_intersect($rules['status_ids'], array_filter([$statusId, $reblogId]));
            //     if (! empty($matchingIds)) {
            //         $statusMatches = $matchingIds;
            //     }
            // }

            if (! empty($keywordMatches) || ! empty($statusMatches)) {
                $results[] = [
                    'filter' => $filter->toFilterArray(),
                    'keyword_matches' => $keywordMatches ?: null,
                    'status_matches' => ! empty($statusMatches) ? $statusMatches : null,
                ];
            }
        }

        return $results;
    }
}
