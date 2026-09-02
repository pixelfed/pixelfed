<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Jobs\MediaPipeline\MediaSyncLicensePipeline;
use App\Models\Avatar;
use App\Models\Profile;
use App\Models\Status;
use App\Services\AccountService;
use App\Services\BookmarkService;
use App\Services\BouncerService;
use App\Services\FollowerService;
use App\Services\InstanceService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Services\UserRoleService;
use App\Transformer\Api\AccountTransformer;
use App\Transformer\Api\StatusTransformer;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\PrettyNumber;
use App\Util\Localization\Localization;
use App\Util\Media\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait AccountsProfile
{
    /**
     * GET /api/v1/accounts/verify_credentials
     *
     *
     * @return AccountTransformer
     */
    public function verifyCredentials(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();

        abort_if($user->status != null, 403);
        AccountService::setLastActive($user->id);

        $res = $request->has(self::PF_API_ENTITY_KEY) ? AccountService::get($user->profile_id) : AccountService::getMastodon($user->profile_id);

        $res['source'] = [
            'privacy' => $res['locked'] ? 'private' : 'public',
            'sensitive' => false,
            'language' => $user->language ?? 'en',
            'note' => strip_tags($res['note']),
            'fields' => [],
        ];

        if ($request->has(self::PF_API_ENTITY_KEY)) {
            $res['settings'] = AccountService::getAccountSettings($user->profile_id);
        }

        return $this->json($res);
    }

    /**
     * GET /api/v1/accounts/{id}
     *
     * @param  int  $id
     * @return AccountTransformer
     */
    public function accountById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $withInstanceMeta = $request->has('_wim');
        $res = $request->has(self::PF_API_ENTITY_KEY) ? AccountService::get($id, true) : AccountService::getMastodon($id, true);
        if (! $res) {
            return response()->json(['error' => 'Record not found'], 404);
        }
        if ($res && strpos($res['acct'], '@') != -1) {
            $domain = parse_url($res['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        return $this->json($res);
    }

    /**
     * GET /api/v1/accounts/lookup
     *
     * @param  string  $acct
     * @return AccountTransformer
     */
    public function accountLookupById(Request $request)
    {
        $request->validate([
            'acct' => 'required|string|min:3|max:100',
        ]);

        $acct = $request->acct;

        if (str_contains($acct, '@')) {
            $count = mb_substr_count($acct, '@');

            if ($count === 1) {
                if (str_starts_with($acct, '@')) {
                    $acct = substr($acct, 1);
                } else {
                    $acct = '@'.$acct;
                }
            }
            if ($count > 2) {
                return $this->json(['error' => 'Record not found'], 400);
            }
        }
        $profile = Profile::whereUsername($acct)->first();

        if (! $profile) {
            return $this->json(['error' => 'Record not found'], 400);
        }

        $res = $request->has(self::PF_API_ENTITY_KEY) ? AccountService::get($profile->id, true) : AccountService::getMastodon($profile->id, true);
        if (! $res) {
            return response()->json(['error' => 'Record not found'], 404);
        }
        if ($res && strpos($res['acct'], '@') != -1) {
            $domain = parse_url($res['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        return $this->json($res);
    }

    /**
     * PATCH /api/v1/accounts/update_credentials
     *
     * @return AccountTransformer
     */
    public function accountUpdateCredentials(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_api')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $this->validate($request, [
            'avatar' => 'sometimes|mimetypes:image/jpeg,image/jpg,image/png|max:'.config('pixelfed.max_avatar_size'),
            'display_name' => 'nullable|string|max:30',
            'note' => 'nullable|string|max:200',
            'locked' => 'nullable',
            'website' => 'nullable|string|max:120',
            // 'source.privacy'    => 'nullable|in:unlisted,public,private',
            // 'source.sensitive'  => 'nullable|boolean'
        ], [
            'required' => 'The :attribute field is required.',
            'avatar.mimetypes' => 'The file must be in jpeg or png format',
            'avatar.max' => 'The :attribute exceeds the file size limit of '.PrettyNumber::size(config('pixelfed.max_avatar_size'), true, false),
        ]);

        $user = $request->user();
        AccountService::setLastActive($user->id);
        $profile = $user->profile;
        $settings = $user->settings;

        $changes = false;
        $other = array_merge(AccountService::defaultSettings()['other'], $settings->other ?? []);
        $syncLicenses = false;
        $licenseChanged = false;
        $composeSettings = array_merge(AccountService::defaultSettings()['compose_settings'], $settings->compose_settings ?? []);

        if ($request->has('avatar')) {
            $av = Avatar::whereProfileId($profile->id)->first();
            if ($av) {
                $currentAvatar = storage_path('app/'.$av->media_path);
                $file = $request->file('avatar');
                $path = "public/avatars/{$profile->id}";
                $name = strtolower(Str::random(6)).'.'.$file->guessExtension();
                $request->file('avatar')->storePubliclyAs($path, $name);
                $av->media_path = "{$path}/{$name}";
                $av->save();
                Cache::forget("avatar:{$profile->id}");
                Cache::forget('user:account:id:'.$user->id);
                AvatarOptimize::dispatch($user->profile, $currentAvatar);
            }
            $changes = true;
        }

        if ($request->has('source[language]')) {
            $lang = $request->input('source[language]');
            if (in_array($lang, Localization::languages())) {
                $user->language = $lang;
                $changes = true;
                $other['language'] = $lang;
            }
        }

        if ($request->has('website')) {
            $website = $request->input('website');
            if ($website != $profile->website) {
                if ($website) {
                    // Validate URL scheme FIRST
                    if (! Str::startsWith($website, ['http://', 'https://'])) {
                        $website = null;
                    } else {
                        if (! strpos($website, '.')) {
                            $website = null;
                        }

                        $host = parse_url($website, PHP_URL_HOST);

                        $bannedInstances = InstanceService::getBannedDomains();
                        if (in_array($host, $bannedInstances)) {
                            $website = null;
                        }
                    }
                }
                $profile->website = $website;
                $changes = true;
            }
        }

        if ($request->has('display_name')) {
            $displayName = $request->input('display_name');
            if ($displayName !== $user->name) {
                $user->name = $displayName;
                $profile->name = $displayName;
                $changes = true;
            }
        }

        if ($request->has('note')) {
            $note = $request->input('note');
            if ($note !== strip_tags($profile->bio)) {
                $profile->bio = Autolink::create()->autolink(strip_tags($note));
                $changes = true;
            }
        }

        if ($request->has('locked')) {
            $locked = $request->boolean('locked');
            if ($profile->is_private != $locked) {
                $profile->is_private = $locked;
                $changes = true;
            }
        }

        if ($request->has('reduce_motion')) {
            $reduced = $request->boolean('reduce_motion');
            if ($settings->reduce_motion != $reduced) {
                $settings->reduce_motion = $reduced;
                $changes = true;
            }
        }

        if ($request->has('high_contrast_mode')) {
            $contrast = $request->boolean('high_contrast_mode');
            if ($settings->high_contrast_mode != $contrast) {
                $settings->high_contrast_mode = $contrast;
                $changes = true;
            }
        }

        if ($request->has('video_autoplay')) {
            $autoplay = $request->boolean('video_autoplay');
            if ($settings->video_autoplay != $autoplay) {
                $settings->video_autoplay = $autoplay;
                $changes = true;
            }
        }

        if ($request->has('license')) {
            $license = $request->input('license');
            abort_if(! in_array($license, License::keys()), 422, 'Invalid media license id');
            $syncLicenses = $request->input('sync_licenses') == true;
            abort_if($syncLicenses && Cache::get('pf:settings:mls_recently:'.$user->id) == 2, 422, 'You can only sync licenses twice per 24 hours');
            if ($composeSettings['default_license'] != $license) {
                $composeSettings['default_license'] = $license;
                $licenseChanged = true;
                $changes = true;
            }
        }

        if ($request->has('media_descriptions')) {
            $md = $request->boolean('media_descriptions');
            if ($composeSettings['media_descriptions'] != $md) {
                $composeSettings['media_descriptions'] = $md;
                $changes = true;
            }
        }

        if ($request->has('crawlable')) {
            $crawlable = $request->boolean('crawlable');
            if ($settings->crawlable != $crawlable) {
                $settings->crawlable = $crawlable;
                $changes = true;
            }
        }

        if ($request->has('show_profile_follower_count')) {
            $show_profile_follower_count = $request->boolean('show_profile_follower_count');
            if ($settings->show_profile_follower_count != $show_profile_follower_count) {
                $settings->show_profile_follower_count = $show_profile_follower_count;
                $changes = true;
                Cache::forget('pf:acct-trans:hideFollowers:'.$profile->id);
            }
        }

        if ($request->has('show_profile_following_count')) {
            $show_profile_following_count = $request->boolean('show_profile_following_count');
            if ($settings->show_profile_following_count != $show_profile_following_count) {
                $settings->show_profile_following_count = $show_profile_following_count;
                $changes = true;
                Cache::forget('pf:acct-trans:hideFollowing:'.$profile->id);
            }
        }

        if ($request->has('public_dm')) {
            $public_dm = $request->boolean('public_dm');
            if ($settings->public_dm != $public_dm) {
                $settings->public_dm = $public_dm;
                $changes = true;
            }
        }

        if ($request->has('source[privacy]')) {
            $scope = $request->input('source[privacy]');
            if (in_array($scope, ['public', 'private', 'unlisted'])) {
                if ($composeSettings['default_scope'] != $scope) {
                    $composeSettings['default_scope'] = $profile->is_private ? 'private' : $scope;
                    $changes = true;
                }
            }
        }

        if ($request->has('disable_embeds')) {
            $disabledEmbeds = $request->boolean('disable_embeds');
            if ($other['disable_embeds'] != $disabledEmbeds) {
                $other['disable_embeds'] = $disabledEmbeds;
                $changes = true;
            }
        }

        if ($request->has('show_atom')) {
            $showAtom = $request->boolean('show_atom');
            if ($settings->show_atom != $showAtom) {
                $settings->show_atom = $showAtom;
                $changes = true;
            }
        }

        if ($request->has('is_suggestable')) {
            $isSuggestable = $request->boolean('is_suggestable');
            if ($profile->is_suggestable != $isSuggestable) {
                $profile->is_suggestable = $isSuggestable;
                $changes = true;
            }
        }

        if ($changes) {
            $settings->other = $other;
            $settings->compose_settings = $composeSettings;
            $settings->save();
            $user->save();
            $profile->save();
            Cache::forget('profile:settings:'.$profile->id);
            Cache::forget('user:account:id:'.$profile->user_id);
            Cache::forget('profile:follower_count:'.$profile->id);
            Cache::forget('profile:following_count:'.$profile->id);
            Cache::forget('profile:embed:'.$profile->id);
            Cache::forget('profile:compose:settings:'.$user->id);
            Cache::forget('profile:view:'.$profile->username);
            Cache::forget('profile:atom:enabled:'.$profile->id);
            Cache::forget('pfc:cached-user:wt:'.strtolower($profile->username));
            Cache::forget('pfc:cached-user:wot:'.strtolower($profile->username));
            Cache::forget('pf:acct:settings:hidden-followers:'.$profile->id);
            Cache::forget('pf:acct:settings:hidden-following:'.$profile->id);
            Cache::forget('pf:acct-trans:hideFollowing:'.$profile->id);
            Cache::forget('pf:acct-trans:hideFollowers:'.$profile->id);
            AccountService::del($user->profile_id);
            AccountService::forgetAccountSettings($profile->id);
        }

        if ($syncLicenses && $licenseChanged) {
            $key = 'pf:settings:mls_recently:'.$user->id;
            $val = Cache::has($key) ? 2 : 1;
            Cache::put($key, $val, 86400);
            MediaSyncLicensePipeline::dispatch($user->id, $request->input('license'));
        }

        if ($request->has(self::PF_API_ENTITY_KEY)) {
            $res = AccountService::get($user->profile_id, true);
        } else {
            $res = AccountService::getMastodon($user->profile_id, true);
            $res['bio'] = strip_tags($res['note']);
            $res = array_merge($res, $other);
        }

        return $this->json($res);
    }

    /**
     * GET /api/v1/accounts/{id}/statuses
     *
     * @param  int  $id
     * @return StatusTransformer
     */
    public function accountStatusesById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();

        $this->validate($request, [
            'only_media' => 'nullable',
            'media_type' => 'sometimes|string|in:photo,video',
            'pinned' => 'nullable',
            'exclude_replies' => 'nullable',
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'since_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'nullable|integer|min:1',
        ]);

        $napi = $request->has(self::PF_API_ENTITY_KEY);
        $profile = $napi ? AccountService::get($id, true) : AccountService::getMastodon($id, true);

        if (! $profile || ! isset($profile['id']) || ! $user) {
            return $this->json(['error' => 'Account not found'], 404);
        }

        if ($profile && strpos($profile['acct'], '@') != -1) {
            $domain = parse_url($profile['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        $limit = $request->input('limit') ?? 20;
        if ($limit > 40) {
            $limit = 40;
        }
        $max_id = $request->max_id;
        $min_id = $request->min_id;

        if (! $max_id && ! $min_id) {
            $min_id = 0;
        }

        $pid = $request->user()->profile_id;
        $scope = $request->only_media == true ?
            ['photo', 'photo:album', 'video', 'video:album'] :
            ['photo', 'photo:album', 'video', 'video:album', 'share', 'reply'];

        if ($request->only_media && $request->has('media_type')) {
            $mt = $request->input('media_type');
            if ($mt == 'video') {
                $scope = ['video', 'video:album'];
            }
        }

        if (intval($pid) === intval($profile['id'])) {
            $visibility = ['public', 'unlisted', 'private'];
        } elseif ($profile['locked']) {
            $following = FollowerService::follows($pid, $profile['id']);
            if (! $following) {
                return response()->json([]);
            }
            $visibility = ['public', 'unlisted', 'private'];
        } else {
            $following = FollowerService::follows($pid, $profile['id']);
            $visibility = $following ? ['public', 'unlisted', 'private'] : ['public', 'unlisted'];
        }

        $dir = $min_id !== null ? '>' : '<';
        $id = $min_id ?? $max_id;
        $res = Status::select(
            'profile_id',
            'in_reply_to_id',
            'reblog_of_id',
            'type',
            'id',
            'scope',
            'pinned_order'
        )
            ->whereProfileId($profile['id'])
            ->whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereIn('type', $scope)
            ->where('id', $dir, $id)
            ->whereIn('scope', $visibility)
            ->limit($limit)
            ->orderByDesc('id')
            ->get()
            ->map(function ($s) use ($user, $napi, $profile) {
                try {
                    $status = $napi ? StatusService::get($s->id, false) : StatusService::getMastodon($s->id, false);
                } catch (\Exception $e) {
                    return false;
                }

                if ($profile) {
                    $status['account'] = $profile;
                }

                if ($user && $status) {
                    $status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
                    $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $s->id);
                    $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $s->id);
                }

                return $status;
            })
            ->filter(function ($s) {
                return $s;
            })
            ->values();

        return $this->json($res);
    }

    /**
     * GET /api/v1/accounts/search
     *
     *
     *
     * @return AccountTransformer
     */
    public function accountSearch(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'q' => 'required|string|min:1|max:30',
            'limit' => 'nullable|integer|min:1',
            'resolve' => 'nullable',
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-view-discover', $user->id), 403, 'Invalid permissions for this action');

        AccountService::setLastActive($user->id);
        $query = $request->input('q');
        $limit = $request->input('limit') ?? 20;
        if ($limit > 20) {
            $limit = 20;
        }
        $resolve = $request->boolean('resolve', false);
        $q = $query.'%';

        $profiles = Profile::where('username', 'like', $q)
            ->orderByDesc('followers_count')
            ->limit($limit)
            ->pluck('id')
            ->map(function ($id) {
                return AccountService::getMastodon($id);
            })
            ->filter(function ($account) {
                return $account && isset($account['id']);
            })
            ->values();

        return $this->json($profiles);
    }

    /**
     * GET /api/v1/suggestions
     *
     *   Return empty array as we don't support suggestions
     *
     * @return null
     */
    public function accountSuggestions(Request $request): JsonResponse
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        // todo

        return response()->json([]);
    }
}
