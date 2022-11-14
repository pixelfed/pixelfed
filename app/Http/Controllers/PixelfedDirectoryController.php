<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigCache;
use Storage;
use App\Services\AccountService;
use App\Services\StatusService;
use Illuminate\Support\Str;

class PixelfedDirectoryController extends Controller
{
    public function get(Request $request)
    {
        if(!$request->filled('sk')) {
            abort(404);
        }

        if(!config_cache('pixelfed.directory.submission-key')) {
            abort(404);
        }

        if(!hash_equals(config_cache('pixelfed.directory.submission-key'), $request->input('sk'))) {
            abort(403);
        }

        $res = $this->buildListing();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function buildListing()
    {
        $res = config_cache('pixelfed.directory');
        if($res) {
            $res = is_string($res) ? json_decode($res, true) : $res;
        }

        $res['_domain'] = config_cache('pixelfed.domain.app');
        $res['_sk'] = config_cache('pixelfed.directory.submission-key');
        $res['_ts'] = config_cache('pixelfed.directory.submission-ts');
        $res['version'] = config_cache('pixelfed.version');

        if(empty($res['summary'])) {
            $summary = ConfigCache::whereK('app.short_description')->pluck('v');
            $res['summary'] = $summary ? $summary[0] : null;
        }

        if(isset($res['admin'])) {
            $res['admin'] = AccountService::get($res['admin'], true);
        }

        if(isset($res['banner_image']) && !empty($res['banner_image'])) {
            $res['banner_image'] = url(Storage::url($res['banner_image']));
        }

        if(isset($res['favourite_posts'])) {
            $res['favourite_posts'] = collect($res['favourite_posts'])->map(function($id) {
                return StatusService::get($id);
            })
            ->filter(function($post) {
                return $post && isset($post['account']);
            })
            ->map(function($post) {
                return [
                    'avatar' => $post['account']['avatar'],
                    'display_name' => $post['account']['display_name'],
                    'username' => $post['account']['username'],
                    'media' => $post['media_attachments'][0]['url'],
                    'url' => $post['url']
                ];
            })
            ->values();
        }

        $guidelines = ConfigCache::whereK('app.rules')->first();
        if($guidelines) {
            $res['community_guidelines'] = json_decode($guidelines->v, true);
        }

        $openRegistration = ConfigCache::whereK('pixelfed.open_registration')->first();
        if($openRegistration) {
            $res['open_registration'] = (bool) $openRegistration;
        }

        $oauthEnabled = ConfigCache::whereK('pixelfed.oauth_enabled')->first();
        if($oauthEnabled) {
            $keys = file_exists(storage_path('oauth-public.key')) && file_exists(storage_path('oauth-private.key'));
            $res['oauth_enabled'] = (bool) $oauthEnabled && $keys;
        }

        $activityPubEnabled = ConfigCache::whereK('federation.activitypub.enabled')->first();
        if($activityPubEnabled) {
            $res['activitypub_enabled'] = (bool) $activityPubEnabled;
        }

        $res['feature_config'] = [
            'media_types' => Str::of(config_cache('pixelfed.media_types'))->explode(','),
            'image_quality' => config_cache('pixelfed.image_quality'),
            'optimize_image' => config_cache('pixelfed.optimize_image'),
            'max_photo_size' => config_cache('pixelfed.max_photo_size'),
            'max_caption_length' => config_cache('pixelfed.max_caption_length'),
            'max_altext_length' => config_cache('pixelfed.max_altext_length'),
            'enforce_account_limit' => config_cache('pixelfed.enforce_account_limit'),
            'max_account_size' => config_cache('pixelfed.max_account_size'),
            'max_album_length' => config_cache('pixelfed.max_album_length'),
            'account_deletion' => config_cache('pixelfed.account_deletion'),
        ];

        $res['is_eligible'] = $this->validVal($res, 'admin') &&
            $this->validVal($res, 'summary', null, 10) &&
            $this->validVal($res, 'favourite_posts', 3) &&
            $this->validVal($res, 'contact_email') &&
            $this->validVal($res, 'privacy_pledge') &&
            $this->validVal($res, 'location');

        if(config_cache('pixelfed.directory.testimonials')) {
            $res['testimonials'] = collect(json_decode(config_cache('pixelfed.directory.testimonials'), true))
                ->map(function($testimonial) {
                    $profile = AccountService::get($testimonial['profile_id']);
                    return [
                        'profile' => [
                            'username' => $profile['username'],
                            'display_name' => $profile['display_name'],
                            'avatar' => $profile['avatar'],
                            'created_at' => $profile['created_at']
                        ],
                        'body' => $testimonial['body']
                    ];
                });
        }

        $res['features_enabled'] = [
            'stories' => (bool) config_cache('instance.stories.enabled')
        ];

        $res['stats'] = [
            'user_count' => \App\User::count(),
            'post_count' => \App\Status::whereNull('uri')->count(),
        ];

        $res['primary_locale'] = config('app.locale');
        $hash = hash('sha256', json_encode($res));
        $res['_hash'] = $hash;
        ksort($res);

        return $res;
    }

    protected function validVal($res, $val, $count = false, $minLen = false)
    {
        if(!isset($res[$val])) {
            return false;
        }

        if($count) {
            return count($res[$val]) >= $count;
        }

        if($minLen) {
            return strlen($res[$val]) >= $minLen;
        }

        return $res[$val];
    }

}
