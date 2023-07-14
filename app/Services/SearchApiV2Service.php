<?php

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\Redis;
use App\{Hashtag, Profile, Status};
use App\Transformer\Api\AccountTransformer;
use App\Transformer\Api\StatusTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Str;
use App\Services\AccountService;
use App\Services\HashtagService;
use App\Services\StatusService;

class SearchApiV2Service
{
    private $query;
    static $mastodonMode = false;

    public static function query($query, $mastodonMode = false)
    {
        self::$mastodonMode = $mastodonMode;
        return (new self)->run($query);
    }

    protected function run($query)
    {
        $this->query = $query;
        $q = urldecode($query->input('q'));

        if($query->has('resolve') &&
            ( Str::startsWith($q, 'https://') ||
              Str::substrCount($q, '@') >= 1)
        ) {
            return $this->resolveQuery();
        }

        if($query->has('type')) {
            switch ($query->input('type')) {
                case 'accounts':
                    return [
                        'accounts' => $this->accounts(),
                        'hashtags' => [],
                        'statuses' => []
                    ];
                    break;
                case 'hashtags':
                    return [
                        'accounts' => [],
                        'hashtags' => $this->hashtags(),
                        'statuses' => []
                    ];
                    break;
                case 'statuses':
                    return [
                        'accounts' => [],
                        'hashtags' => [],
                        'statuses' => $this->statuses()
                    ];
                    break;
            }
        }

        if($query->has('account_id')) {
            return [
                'accounts' => [],
                'hashtags' => [],
                'statuses' => $this->statusesById()
            ];
        }

        return [
            'accounts' => $this->accounts(),
            'hashtags' => $this->hashtags(),
            'statuses' => $this->statuses()
        ];
    }

    protected function accounts($initalQuery = false)
    {
        $mastodonMode = self::$mastodonMode;
        $user = request()->user();
        $limit = $this->query->input('limit') ?? 20;
        $offset = $this->query->input('offset') ?? 0;
        $rawQuery = $initalQuery ? $initalQuery : $this->query->input('q');
        $query = $rawQuery . '%';
        $webfingerQuery = $query;
        if(Str::substrCount($rawQuery, '@') == 1 && substr($rawQuery, 0, 1) !== '@') {
            $query = '@' . $query;
        }
        if(substr($webfingerQuery, 0, 1) !== '@') {
            $webfingerQuery = '@' . $webfingerQuery;
        }
        $banned = InstanceService::getBannedDomains();
        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';
        $results = Profile::select('username', 'id', 'followers_count', 'domain')
            ->where('username', $operator, $query)
            ->orWhere('webfinger', $operator, $webfingerQuery)
            ->orderByDesc('profiles.followers_count')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->filter(function($profile) use ($banned) {
                return in_array($profile->domain, $banned) == false;
            })
            ->map(function($res) use($mastodonMode) {
                return $mastodonMode ?
                AccountService::getMastodon($res['id']) :
                AccountService::get($res['id']);
            })
            ->filter(function($account) {
                return $account && isset($account['id']);
            })
            ->values();

        return $results;
    }

    protected function hashtags()
    {
        $mastodonMode = self::$mastodonMode;
        $q = $this->query->input('q');
        $limit = $this->query->input('limit') ?? 20;
        $offset = $this->query->input('offset') ?? 0;
        $query = Str::startsWith($q, '#') ? '%' . substr($q, 1) . '%' : '%' . $q . '%';
        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';
        return Hashtag::where('name', $operator, $query)
            ->orWhere('slug', $operator, $query)
            ->where(function($q) {
                return $q->where('can_search', true)
                        ->orWhereNull('can_search');
            })
            ->orderByDesc('cached_count')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function($tag) use($mastodonMode) {
                $res = [
                    'name' => $tag->name,
                    'url'  => $tag->url()
                ];

                if(!$mastodonMode) {
                    $res['history'] = [];
                    $res['count'] = HashtagService::count($tag->id);
                }

                return $res;
            });
    }

    protected function statuses()
    {
        // Removed until we provide more relevent sorting/results
        return [];
    }

    protected function statusesById()
    {
        // Removed until we provide more relevent sorting/results
        return [];
    }

    protected function resolveQuery()
    {
        $default =  [
            'accounts' => [],
            'hashtags' => [],
            'statuses' => [],
        ];
        $mastodonMode = self::$mastodonMode;
        $query = urldecode($this->query->input('q'));
        if(substr($query, 0, 1) === '@' && !Str::contains($query, '.')) {
            $default['accounts'] = $this->accounts(substr($query, 1));
            return $default;
        }
        if(Helpers::validateLocalUrl($query)) {
            if(Str::contains($query, '/p/') || Str::contains($query, 'i/web/post/')) {
                return $this->resolveLocalStatus();
            }  else if(Str::contains($query, 'i/web/profile/')) {
                return $this->resolveLocalProfileId();
            } else {
                return $this->resolveLocalProfile();
            }
        } else {
            if(!Helpers::validateUrl($query) && strpos($query, '@') == -1) {
                return $default;
            }

            if(!Str::startsWith($query, 'http') && Str::substrCount($query, '@') == 1 && strpos($query, '@') !== 0) {
                try {
                    $res = WebfingerService::lookup('@' . $query, $mastodonMode);
                } catch (\Exception $e) {
                    return $default;
                }
                if($res && isset($res['id'])) {
                    $default['accounts'][] = $res;
                    return $default;
                } else {
                    return $default;
                }
            }

            if(Str::substrCount($query, '@') == 2) {
                try {
                    $res = WebfingerService::lookup($query, $mastodonMode);
                } catch (\Exception $e) {
                    return $default;
                }
                if($res && isset($res['id'])) {
                    $default['accounts'][] = $res;
                    return $default;
                } else {
                    return $default;
                }
            }

            if($sid = Status::whereUri($query)->first()) {
                $s = StatusService::get($sid->id, false);
                if(in_array($s['visibility'], ['public', 'unlisted'])) {
                    $default['statuses'][] = $s;
                    return $default;
                }
            }

            try {
                $res = ActivityPubFetchService::get($query);
                $banned = InstanceService::getBannedDomains();
                if($res) {
                    $json = json_decode($res, true);

                    if(!$json || !isset($json['@context']) || !isset($json['type']) || !in_array($json['type'], ['Note', 'Person'])) {
                        return [
                            'accounts' => [],
                            'hashtags' => [],
                            'statuses' => [],
                        ];
                    }

                    switch($json['type']) {
                        case 'Note':
                            $obj = Helpers::statusFetch($query);
                            if(!$obj || !isset($obj['id'])) {
                                return $default;
                            }
                            $note = $mastodonMode ?
                                StatusService::getMastodon($obj['id'], false) :
                                StatusService::get($obj['id'], false);
                            if(!$note) {
                                return $default;
                            }
                            if(!isset($note['visibility']) || !in_array($note['visibility'], ['public', 'unlisted'])) {
                                return $default;
                            }
                            $default['statuses'][] = $note;
                            return $default;
                        break;

                        case 'Person':
                            $obj = Helpers::profileFetch($query);
                            if(!$obj) {
                                return $default;
                            }
                            if(in_array($obj['domain'], $banned)) {
                                return $default;
                            }
                            $default['accounts'][] = $mastodonMode ?
                                AccountService::getMastodon($obj['id'], true) :
                                AccountService::get($obj['id'], true);
                            return $default;
                        break;

                        default:
                            return [
                                'accounts' => [],
                                'hashtags' => [],
                                'statuses' => [],
                            ];
                        break;
                    }
                }
            } catch (\Exception $e) {
                return [
                    'accounts' => [],
                    'hashtags' => [],
                    'statuses' => [],
                ];
            }

            return $default;
        }
    }

    protected function resolveLocalStatus()
    {
        $query = urldecode($this->query->input('q'));
        $query = last(explode('/', parse_url($query, PHP_URL_PATH)));
        $status = StatusService::getMastodon($query, false);
        if(!$status || !in_array($status['visibility'], ['public', 'unlisted'])) {
            return [
                'accounts' => [],
                'hashtags' => [],
                'statuses' => []
            ];
        }

        $res = [
            'accounts' => [],
            'hashtags' => [],
            'statuses' => [$status]
        ];

        return $res;
    }

    protected function resolveLocalProfile()
    {
        $query = urldecode($this->query->input('q'));
        $query = last(explode('/', parse_url($query, PHP_URL_PATH)));
        $profile = Profile::whereNull('status')
            ->whereNull('domain')
            ->whereUsername($query)
            ->first();

        if(!$profile) {
            return [
                'accounts' => [],
                'hashtags' => [],
                'statuses' => []
            ];
        }

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
        return [
            'accounts' => [$fractal->createData($resource)->toArray()],
            'hashtags' => [],
            'statuses' => []
        ];
    }

    protected function resolveLocalProfileId()
    {
        $query = urldecode($this->query->input('q'));
        $query = last(explode('/', parse_url($query, PHP_URL_PATH)));
        $profile = Profile::whereNull('status')
            ->find($query);

        if(!$profile) {
            return [
                'accounts' => [],
                'hashtags' => [],
                'statuses' => []
            ];
        }

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
        return [
            'accounts' => [$fractal->createData($resource)->toArray()],
            'hashtags' => [],
            'statuses' => []
        ];
    }

}
