<?php

namespace App\Http\Controllers\Admin;

use DB, Cache;
use App\{
    DiscoverCategory,
    DiscoverCategoryHashtag,
    Hashtag,
    Media,
    Profile,
    Status,
    StatusHashtag,
    User
};
use App\Models\ConfigCache;
use App\Services\AccountService;
use App\Services\ConfigCacheService;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use League\ISO3166\ISO3166;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\PixelfedDirectoryController;

trait AdminDirectoryController
{
    public function directoryHome(Request $request)
    {
        return view('admin.directory.home');
    }

    public function directoryInitialData(Request $request)
    {
        $res = [];

        $res['countries'] = collect((new ISO3166)->all())->pluck('name');
        $res['admins'] = User::whereIsAdmin(true)
            ->where('2fa_enabled', true)
            ->get()->map(function($user) {
            return [
                'uid' => (string) $user->id,
                'pid' => (string) $user->profile_id,
                'username' => $user->username,
                'created_at' => $user->created_at
            ];
        });
        $config = ConfigCache::whereK('pixelfed.directory')->first();
        if($config) {
            $data = $config->v ? json_decode($config->v, true) : [];
            $res = array_merge($res, $data);
        }

        if(empty($res['summary'])) {
            $summary = ConfigCache::whereK('app.short_description')->pluck('v');
            $res['summary'] = $summary ? $summary[0] : null;
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
            ->values();
        }

        $res['community_guidelines'] = config_cache('app.rules') ? json_decode(config_cache('app.rules'), true) : [];
        $res['open_registration'] = (bool) config_cache('pixelfed.open_registration');
        $res['oauth_enabled'] = (bool) config_cache('pixelfed.oauth_enabled') && file_exists(storage_path('oauth-public.key')) && file_exists(storage_path('oauth-private.key'));

        $res['activitypub_enabled'] = (bool) config_cache('federation.activitypub.enabled');

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

        if(config_cache('pixelfed.directory.testimonials')) {
            $testimonials = collect(json_decode(config_cache('pixelfed.directory.testimonials'),true))
                ->map(function($t) {
                    return [
                        'profile' => AccountService::get($t['profile_id']),
                        'body' => $t['body']
                    ];
                });
            $res['testimonials'] = $testimonials;
        }

        $validator = Validator::make($res['feature_config'], [
            'media_types' => [
                'required',
                 function ($attribute, $value, $fail) {
                    if (!in_array('image/jpeg', $value->toArray()) || !in_array('image/png', $value->toArray())) {
                        $fail('You must enable image/jpeg and image/png support.');
                    }
                },
            ],
            'image_quality' => 'required_if:optimize_image,true|integer|min:75|max:100',
            'max_altext_length' => 'required|integer|min:1000|max:5000',
            'max_photo_size' => 'required|integer|min:15000|max:100000',
            'max_account_size' => 'required_if:enforce_account_limit,true|integer|min:1000000',
            'max_album_length' => 'required|integer|min:4|max:20',
            'account_deletion' => 'required|accepted',
            'max_caption_length' => 'required|integer|min:500|max:10000'
        ]);

        $res['requirements_validator'] = $validator->errors();

        $res['is_eligible'] = $res['open_registration'] &&
            $res['oauth_enabled'] &&
            $res['activitypub_enabled'] &&
            count($res['requirements_validator']) === 0 &&
            $this->validVal($res, 'admin') &&
            $this->validVal($res, 'summary', null, 10) &&
            $this->validVal($res, 'favourite_posts', 3) &&
            $this->validVal($res, 'contact_email') &&
            $this->validVal($res, 'privacy_pledge') &&
            $this->validVal($res, 'location');

        $res['has_submitted'] = config_cache('pixelfed.directory.has_submitted') ?? false;
        $res['synced'] = config_cache('pixelfed.directory.is_synced') ?? false;
        $res['latest_response'] = config_cache('pixelfed.directory.latest_response') ?? null;

        $path = base_path('resources/lang');
        $langs = collect([]);

        foreach (new \DirectoryIterator($path) as $io) {
            $name = $io->getFilename();
            $skip = ['vendor'];
            if($io->isDot() || in_array($name, $skip)) {
                continue;
            }

            if($io->isDir()) {
                $langs->push(['code' => $name, 'name' => locale_get_display_name($name)]);
            }
        }

        $res['available_languages'] = $langs->sortBy('name')->values();
        $res['primary_locale'] = config('app.locale');

        $submissionState = Http::withoutVerifying()
        ->post('https://pixelfed.org/api/v1/directory/check-submission', [
            'domain' => config('pixelfed.domain.app')
        ]);

        $res['submission_state'] = $submissionState->json();
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

    public function directoryStore(Request $request)
    {
        $this->validate($request, [
            'location' => 'string|min:1|max:53',
            'summary' => 'string|nullable|max:140',
            'admin_uid' => 'sometimes|nullable',
            'contact_email' => 'sometimes|nullable|email:rfc,dns',
            'favourite_posts' => 'array|max:12',
            'favourite_posts.*' => 'distinct',
            'privacy_pledge' => 'sometimes',
            'banner_image' => 'sometimes|mimes:jpg,png|dimensions:width=1920,height:1080|max:5000'
        ]);

        $config = ConfigCache::firstOrNew([
            'k' => 'pixelfed.directory'
        ]);

        $res = $config->v ? json_decode($config->v, true) : [];
        $res['summary'] = strip_tags($request->input('summary'));
        $res['favourite_posts'] = $request->input('favourite_posts');
        $res['admin'] = (string) $request->input('admin_uid');
        $res['contact_email'] = $request->input('contact_email');
        $res['privacy_pledge'] = (bool) $request->input('privacy_pledge');

        if($request->filled('location')) {
            $exists = (new ISO3166)->name($request->location);
            if($exists) {
                $res['location'] = $request->input('location');
            }
        }

        if($request->hasFile('banner_image')) {
            collect(Storage::files('public/headers'))
            ->filter(function($name) {
                $protected = [
                    'public/headers/.gitignore',
                    'public/headers/default.jpg',
                    'public/headers/missing.png'
                ];
                return !in_array($name, $protected);
            })
            ->each(function($name) {
                Storage::delete($name);
            });
            $path = $request->file('banner_image')->store('public/headers');
            $res['banner_image'] = $path;
            ConfigCacheService::put('app.banner_image', url(Storage::url($path)));

            Cache::forget('api:v1:instance-data-response-v1');
        }

        $config->v = json_encode($res);
        $config->save();

        ConfigCacheService::put('pixelfed.directory', $config->v);
        $updated = json_decode($config->v, true);
        if(isset($updated['banner_image'])) {
            $updated['banner_image'] = url(Storage::url($updated['banner_image']));
        }
        return $updated;
    }

    public function directoryHandleServerSubmission(Request $request)
    {
        $reqs = [];
        $reqs['feature_config'] = [
            'open_registration' => config_cache('pixelfed.open_registration'),
            'activitypub_enabled' => config_cache('federation.activitypub.enabled'),
            'oauth_enabled' => config_cache('pixelfed.oauth_enabled'),
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

        $validator = Validator::make($reqs['feature_config'], [
            'open_registration' => 'required|accepted',
            'activitypub_enabled' => 'required|accepted',
            'oauth_enabled' => 'required|accepted',
            'media_types' => [
                'required',
                 function ($attribute, $value, $fail) {
                    if (!in_array('image/jpeg', $value->toArray()) || !in_array('image/png', $value->toArray())) {
                        $fail('You must enable image/jpeg and image/png support.');
                    }
                },
            ],
            'image_quality' => 'required_if:optimize_image,true|integer|min:75|max:100',
            'max_altext_length' => 'required|integer|min:1000|max:5000',
            'max_photo_size' => 'required|integer|min:15000|max:100000',
            'max_account_size' => 'required_if:enforce_account_limit,true|integer|min:1000000',
            'max_album_length' => 'required|integer|min:4|max:20',
            'account_deletion' => 'required|accepted',
            'max_caption_length' => 'required|integer|min:500|max:10000'
        ]);

        if(!$validator->validate()) {
            return response()->json($validator->errors(), 422);
        }

        ConfigCacheService::put('pixelfed.directory.submission-key', Str::random(random_int(40, 69)));
        ConfigCacheService::put('pixelfed.directory.submission-ts', now());

        $data = (new PixelfedDirectoryController())->buildListing();
        $res = Http::withoutVerifying()->post('https://pixelfed.org/api/v1/directory/submission', $data);
        return 200;
    }

    public function directoryDeleteBannerImage(Request $request)
    {
        $bannerImage = ConfigCache::whereK('app.banner_image')->first();
        $directory = ConfigCache::whereK('pixelfed.directory')->first();
        if(!$bannerImage && !$directory || empty($directory->v)) {
            return;
        }
        $directoryArr = json_decode($directory->v, true);
        $path = isset($directoryArr['banner_image']) ? $directoryArr['banner_image'] : false;
        $protected = [
            'public/headers/.gitignore',
            'public/headers/default.jpg',
            'public/headers/missing.png'
        ];
        if(!$path || in_array($path, $protected)) {
            return;
        }
        if(Storage::exists($directoryArr['banner_image'])) {
            Storage::delete($directoryArr['banner_image']);
        }

        $directoryArr['banner_image'] = 'public/headers/default.jpg';
        $directory->v = $directoryArr;
        $directory->save();
        $bannerImage->v = url(Storage::url('public/headers/default.jpg'));
        $bannerImage->save();
        Cache::forget('api:v1:instance-data-response-v1');
        ConfigCacheService::put('pixelfed.directory', $directory);
        return $bannerImage->v;
    }

    public function directoryGetPopularPosts(Request $request)
    {
        $ids = Cache::remember('admin:api:popular_posts', 86400, function() {
            return Status::whereLocal(true)
                ->whereScope('public')
                ->whereType('photo')
                ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                ->orderByDesc('likes_count')
                ->take(50)
                ->pluck('id');
        });

        $res = $ids->map(function($id) {
            return StatusService::get($id);
        })
        ->filter(function($post) {
            return $post && isset($post['account']);
        })
        ->values();

        return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function directoryGetAddPostByIdSearch(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|integer'
        ]);

        $id = $request->input('q');

        $status = Status::whereLocal(true)
            ->whereType('photo')
            ->whereNull(['in_reply_to_id', 'reblog_of_id'])
            ->findOrFail($id);

        $res = StatusService::get($status->id);

        return $res;
    }

    public function directoryDeleteTestimonial(Request $request)
    {
        $this->validate($request, [
            'profile_id' => 'required',
        ]);
        $profile_id = $request->input('profile_id');
        $testimonials = ConfigCache::whereK('pixelfed.directory.testimonials')->firstOrFail();
        $existing = collect(json_decode($testimonials->v, true))
            ->filter(function($t) use($profile_id) {
                return $t['profile_id'] !== $profile_id;
            })
            ->values();
        ConfigCacheService::put('pixelfed.directory.testimonials', $existing);
        return $existing;
    }

    public function directorySaveTestimonial(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'body' => 'required|string|min:5|max:500'
        ]);

        $user = User::whereUsername($request->input('username'))->whereNull('status')->firstOrFail();

        $configCache = ConfigCache::firstOrCreate([
            'k' => 'pixelfed.directory.testimonials'
        ]);

        $testimonials = $configCache->v ? collect(json_decode($configCache->v, true)) : collect([]);

        abort_if($testimonials->contains('profile_id', $user->profile_id), 422, 'Testimonial already exists');
        abort_if($testimonials->count() == 10, 422, 'You can only have 10 active testimonials');

        $testimonials->push([
            'profile_id' => (string) $user->profile_id,
            'username' => $request->input('username'),
            'body' => $request->input('body')
        ]);

        $configCache->v = json_encode($testimonials->toArray());
        $configCache->save();
        ConfigCacheService::put('pixelfed.directory.testimonials', $configCache->v);
        $res = [
            'profile' => AccountService::get($user->profile_id),
            'body' => $request->input('body')
        ];
        return $res;
    }

    public function directoryUpdateTestimonial(Request $request)
    {
        $this->validate($request, [
            'profile_id' => 'required',
            'body' => 'required|string|min:5|max:500'
        ]);

        $profile_id = $request->input('profile_id');
        $body = $request->input('body');
        $user = User::whereProfileId($profile_id)->firstOrFail();

        $configCache = ConfigCache::firstOrCreate([
            'k' => 'pixelfed.directory.testimonials'
        ]);

        $testimonials = $configCache->v ? collect(json_decode($configCache->v, true)) : collect([]);

        $updated = $testimonials->map(function($t) use($profile_id, $body) {
            if($t['profile_id'] == $profile_id) {
                $t['body'] = $body;
            }
            return $t;
        })
        ->values();

        $configCache->v = json_encode($updated);
        $configCache->save();
        ConfigCacheService::put('pixelfed.directory.testimonials', $configCache->v);

        return $updated;
    }
}
