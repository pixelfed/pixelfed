<?php

namespace App\Http\Controllers\Admin;

use App\Models\ConfigCache;
use App\Models\InstanceActor;
use App\Page;
use App\Profile;
use App\Services\AccountService;
use App\Services\AdminSettingsService;
use App\Services\ConfigCacheService;
use App\Services\FilesystemService;
use App\User;
use App\Util\Site\Config;
use Artisan;
use Cache;
use DB;
use Illuminate\Http\Request;

trait AdminSettingsController
{
    public function settings(Request $request)
    {
        $cloud_storage = ConfigCacheService::get('pixelfed.cloud_storage');
        $cloud_disk = config('filesystems.cloud');
        $cloud_ready = ! empty(config('filesystems.disks.'.$cloud_disk.'.key')) && ! empty(config('filesystems.disks.'.$cloud_disk.'.secret'));
        $types = explode(',', ConfigCacheService::get('pixelfed.media_types'));
        $rules = ConfigCacheService::get('app.rules') ? json_decode(ConfigCacheService::get('app.rules'), true) : null;
        $jpeg = in_array('image/jpg', $types) || in_array('image/jpeg', $types);
        $png = in_array('image/png', $types);
        $gif = in_array('image/gif', $types);
        $mp4 = in_array('video/mp4', $types);
        $webp = in_array('image/webp', $types);

        $availableAdmins = User::whereIsAdmin(true)->get();
        $currentAdmin = config_cache('instance.admin.pid') ? AccountService::get(config_cache('instance.admin.pid'), true) : null;
        $openReg = (bool) config_cache('pixelfed.open_registration');
        $curOnboarding = (bool) config_cache('instance.curated_registration.enabled');
        $regState = $openReg ? 'open' : ($curOnboarding ? 'filtered' : 'closed');
        $accountMigration = (bool) config_cache('federation.migration');

        return view('admin.settings.home', compact(
            'jpeg',
            'png',
            'gif',
            'mp4',
            'webp',
            'rules',
            'cloud_storage',
            'cloud_disk',
            'cloud_ready',
            'availableAdmins',
            'currentAdmin',
            'regState',
            'accountMigration'
        ));
    }

    public function settingsHomeStore(Request $request)
    {
        $this->validate($request, [
            'name' => 'nullable|string',
            'short_description' => 'nullable',
            'long_description' => 'nullable',
            'max_photo_size' => 'nullable|integer|min:1',
            'max_album_length' => 'nullable|integer|min:1|max:100',
            'image_quality' => 'nullable|integer|min:1|max:100',
            'type_jpeg' => 'nullable',
            'type_png' => 'nullable',
            'type_gif' => 'nullable',
            'type_mp4' => 'nullable',
            'type_webp' => 'nullable',
            'admin_account_id' => 'nullable',
            'regs' => 'required|in:open,filtered,closed',
            'account_migration' => 'nullable',
            'rule_delete' => 'sometimes',
        ]);

        $orb = false;
        $cob = false;
        switch ($request->input('regs')) {
            case 'open':
                $orb = true;
                $cob = false;
                break;

            case 'filtered':
                $orb = false;
                $cob = true;
                break;

            case 'closed':
                $orb = false;
                $cob = false;
                break;
        }

        ConfigCacheService::put('pixelfed.open_registration', (bool) $orb);
        ConfigCacheService::put('instance.curated_registration.enabled', (bool) $cob);

        if ($request->filled('admin_account_id')) {
            ConfigCacheService::put('instance.admin.pid', $request->admin_account_id);
            Cache::forget('api:v1:instance-data:contact');
            Cache::forget('api:v1:instance-data-response-v1');
        }
        if ($request->filled('rule_delete')) {
            $index = (int) $request->input('rule_delete');
            $rules = ConfigCacheService::get('app.rules');
            $json = json_decode($rules, true);
            if (! $rules || empty($json)) {
                return;
            }
            unset($json[$index]);
            $json = json_encode(array_values($json));
            ConfigCacheService::put('app.rules', $json);
            Cache::forget('api:v1:instance-data:rules');
            Cache::forget('api:v1:instance-data-response-v1');

            return 200;
        }

        $media_types = explode(',', config_cache('pixelfed.media_types'));
        $media_types_original = $media_types;

        $mimes = [
            'type_jpeg' => 'image/jpeg',
            'type_png' => 'image/png',
            'type_gif' => 'image/gif',
            'type_mp4' => 'video/mp4',
            'type_webp' => 'image/webp',
        ];

        foreach ($mimes as $key => $value) {
            if ($request->input($key) == 'on') {
                if (! in_array($value, $media_types)) {
                    array_push($media_types, $value);
                }
            } else {
                $media_types = array_diff($media_types, [$value]);
            }
        }

        if ($media_types !== $media_types_original) {
            ConfigCacheService::put('pixelfed.media_types', implode(',', array_unique($media_types)));
        }

        $keys = [
            'name' => 'app.name',
            'short_description' => 'app.short_description',
            'long_description' => 'app.description',
            'max_photo_size' => 'pixelfed.max_photo_size',
            'max_album_length' => 'pixelfed.max_album_length',
            'image_quality' => 'pixelfed.image_quality',
            'account_limit' => 'pixelfed.max_account_size',
            'custom_css' => 'uikit.custom.css',
            'custom_js' => 'uikit.custom.js',
            'about_title' => 'about.title',
        ];

        foreach ($keys as $key => $value) {
            $cc = ConfigCache::whereK($value)->first();
            $val = $request->input($key);
            if ($cc && $cc->v != $val) {
                ConfigCacheService::put($value, $val);
            } elseif (! empty($val)) {
                ConfigCacheService::put($value, $val);
            }
        }

        $bools = [
            'activitypub' => 'federation.activitypub.enabled',
            // 'open_registration' => 'pixelfed.open_registration',
            'mobile_apis' => 'pixelfed.oauth_enabled',
            'stories' => 'instance.stories.enabled',
            'ig_import' => 'pixelfed.import.instagram.enabled',
            'spam_detection' => 'pixelfed.bouncer.enabled',
            'require_email_verification' => 'pixelfed.enforce_email_verification',
            'enforce_account_limit' => 'pixelfed.enforce_account_limit',
            'show_custom_css' => 'uikit.show_custom.css',
            'show_custom_js' => 'uikit.show_custom.js',
            'cloud_storage' => 'pixelfed.cloud_storage',
            'account_autofollow' => 'account.autofollow',
            'show_directory' => 'instance.landing.show_directory',
            'show_explore_feed' => 'instance.landing.show_explore',
            'account_migration' => 'federation.migration',
        ];

        foreach ($bools as $key => $value) {
            $active = $request->input($key) == 'on';

            if ($key == 'activitypub' && $active && ! InstanceActor::exists()) {
                Artisan::call('instance:actor');
            }

            if ($key == 'mobile_apis' &&
                $active &&
                ! file_exists(storage_path('oauth-public.key')) &&
                ! config_cache('passport.public_key') &&
                ! file_exists(storage_path('oauth-private.key')) &&
                ! config_cache('passport.private_key')
            ) {
                Artisan::call('passport:keys');
                Artisan::call('route:cache');
            }

            if (config_cache($value) !== $active) {
                ConfigCacheService::put($value, (bool) $active);
            }
        }

        if ($request->filled('new_rule')) {
            $rules = ConfigCacheService::get('app.rules');
            $val = $request->input('new_rule');
            if (! $rules) {
                ConfigCacheService::put('app.rules', json_encode([$val]));
            } else {
                $json = json_decode($rules, true);
                $json[] = $val;
                ConfigCacheService::put('app.rules', json_encode(array_values($json)));
            }
            Cache::forget('api:v1:instance-data:rules');
            Cache::forget('api:v1:instance-data-response-v1');
        }

        if ($request->filled('account_autofollow_usernames')) {
            $usernames = explode(',', $request->input('account_autofollow_usernames'));
            $names = [];

            foreach ($usernames as $n) {
                $p = Profile::whereUsername($n)->first();
                if (! $p) {
                    continue;
                }
                array_push($names, $p->username);
            }

            ConfigCacheService::put('account.autofollow_usernames', implode(',', $names));
        }

        Cache::forget(Config::CACHE_KEY);

        return redirect('/i/admin/settings')->with('status', 'Successfully updated settings!');
    }

    public function settingsBackups(Request $request)
    {
        $path = storage_path('app/'.config('app.name'));
        $files = is_dir($path) ? new \DirectoryIterator($path) : [];

        return view('admin.settings.backups', compact('files'));
    }

    public function settingsMaintenance(Request $request)
    {
        return view('admin.settings.maintenance');
    }

    public function settingsStorage(Request $request)
    {
        $storage = [];

        return view('admin.settings.storage', compact('storage'));
    }

    public function settingsFeatures(Request $request)
    {
        return view('admin.settings.features');
    }

    public function settingsPages(Request $request)
    {
        $pages = Page::orderByDesc('updated_at')->paginate(10);

        return view('admin.pages.home', compact('pages'));
    }

    public function settingsPageEdit(Request $request)
    {
        return view('admin.pages.edit');
    }

    public function settingsSystem(Request $request)
    {
        $sys = [
            'pixelfed' => config('pixelfed.version'),
            'php' => phpversion(),
            'laravel' => app()->version(),
        ];
        switch (config('database.default')) {
            case 'pgsql':
                $exp = DB::raw('select version();');
                $expQuery = $exp->getValue(DB::connection()->getQueryGrammar());
                $sys['database'] = [
                    'name' => 'Postgres',
                    'version' => explode(' ', DB::select($expQuery)[0]->version)[1],
                ];
                break;

            case 'mysql':
                $exp = DB::raw('select version()');
                $expQuery = $exp->getValue(DB::connection()->getQueryGrammar());
                $sys['database'] = [
                    'name' => 'MySQL',
                    'version' => DB::select($expQuery)[0]->{'version()'},
                ];
                break;

            default:
                $sys['database'] = [
                    'name' => 'Unknown',
                    'version' => '?',
                ];
                break;
        }

        return view('admin.settings.system', compact('sys'));
    }

    public function settingsApiFetch(Request $request)
    {
        $cloud_storage = ConfigCacheService::get('pixelfed.cloud_storage');
        $cloud_disk = config('filesystems.cloud');
        $cloud_ready = ! empty(config('filesystems.disks.'.$cloud_disk.'.key')) && ! empty(config('filesystems.disks.'.$cloud_disk.'.secret'));
        $types = explode(',', ConfigCacheService::get('pixelfed.media_types'));
        $rules = ConfigCacheService::get('app.rules') ? json_decode(ConfigCacheService::get('app.rules'), true) : [];
        $jpeg = in_array('image/jpg', $types) || in_array('image/jpeg', $types);
        $png = in_array('image/png', $types);
        $gif = in_array('image/gif', $types);
        $mp4 = in_array('video/mp4', $types);
        $webp = in_array('image/webp', $types);

        $availableAdmins = User::whereIsAdmin(true)->get();
        $currentAdmin = config_cache('instance.admin.pid') ? AccountService::get(config_cache('instance.admin.pid'), true) : null;
        $openReg = (bool) config_cache('pixelfed.open_registration');
        $curOnboarding = (bool) config_cache('instance.curated_registration.enabled');
        $regState = $openReg ? 'open' : ($curOnboarding ? 'filtered' : 'closed');
        $accountMigration = (bool) config_cache('federation.migration');
        $autoFollow = config_cache('account.autofollow_usernames');
        if (strlen($autoFollow) > 3) {
            $autoFollow = explode(',', $autoFollow);
        }

        $res = AdminSettingsService::getAll();

        return response()->json($res);
    }

    public function settingsApiRulesAdd(Request $request)
    {
        $this->validate($request, [
            'rule' => 'required|string|min:5|max:1000',
        ]);

        $rules = ConfigCacheService::get('app.rules');
        $val = $request->input('rule');
        if (! $rules) {
            ConfigCacheService::put('app.rules', json_encode([$val]));
        } else {
            $json = json_decode($rules, true);
            $count = count($json);
            if ($count >= 30) {
                return response()->json(['message' => 'Max rules limit reached, you can set up to 30 rules at a time.'], 400);
            }
            $json[] = $val;
            ConfigCacheService::put('app.rules', json_encode(array_values($json)));
        }
        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Config::refresh();

        return [$val];
    }

    public function settingsApiRulesDelete(Request $request)
    {
        $this->validate($request, [
            'rule' => 'required|string',
        ]);

        $rules = ConfigCacheService::get('app.rules');
        $val = $request->input('rule');

        if (! $rules) {
            return [];
        } else {
            $json = json_decode($rules, true);
            $idx = array_search($val, $json);
            if ($idx !== false) {
                unset($json[$idx]);
                $json = array_values($json);
            }
            ConfigCacheService::put('app.rules', json_encode(array_values($json)));
        }

        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Config::refresh();

        return response()->json($json);
    }

    public function settingsApiRulesDeleteAll(Request $request)
    {
        $rules = ConfigCacheService::get('app.rules');

        if (! $rules) {
            return [];
        } else {
            ConfigCacheService::put('app.rules', json_encode([]));
        }

        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Config::refresh();

        return response()->json([]);
    }

    public function settingsApiAutofollowDelete(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
        ]);

        $username = $request->input('username');
        $names = [];
        $existing = config_cache('account.autofollow_usernames');
        if ($existing) {
            $names = explode(',', $existing);
        }

        if (in_array($username, $names)) {
            $key = array_search($username, $names);
            if ($key !== false) {
                unset($names[$key]);
            }
        }
        ConfigCacheService::put('account.autofollow_usernames', implode(',', $names));

        return response()->json(['accounts' => array_values($names)]);
    }

    public function settingsApiAutofollowAdd(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
        ]);

        $username = $request->input('username');
        $names = [];
        $existing = config_cache('account.autofollow_usernames');
        if ($existing) {
            $names = explode(',', $existing);
        }

        if ($existing && count($names)) {
            if (count($names) >= 5) {
                return response()->json(['message' => 'You can only add up to 5 accounts to be autofollowed.'], 400);
            }
            if (in_array(strtolower($username), array_map('strtolower', $names))) {
                return response()->json(['message' => 'User already exists, please try again.'], 400);
            }
        }

        $p = User::whereUsername($username)->whereNull('status')->first();
        if (! $p || in_array($p->username, $names)) {
            abort(404);
        }
        array_push($names, $p->username);
        ConfigCacheService::put('account.autofollow_usernames', implode(',', $names));

        return response()->json(['accounts' => array_values($names)]);
    }

    public function settingsApiUpdateType(Request $request, $type)
    {
        abort_unless(in_array($type, [
            'posts',
            'platform',
            'home',
            'landing',
            'branding',
            'media',
            'users',
            'storage',
        ]), 400);

        switch ($type) {
            case 'home':
                return $this->settingsApiUpdateHomeType($request);
                break;

            case 'landing':
                return $this->settingsApiUpdateLandingType($request);
                break;

            case 'posts':
                return $this->settingsApiUpdatePostsType($request);
                break;

            case 'platform':
                return $this->settingsApiUpdatePlatformType($request);
                break;

            case 'branding':
                return $this->settingsApiUpdateBrandingType($request);
                break;

            case 'media':
                return $this->settingsApiUpdateMediaType($request);
                break;

            case 'users':
                return $this->settingsApiUpdateUsersType($request);
                break;

            case 'storage':
                return $this->settingsApiUpdateStorageType($request);
                break;

            default:
                abort(404);
                break;
        }
    }

    public function settingsApiUpdateHomeType($request)
    {
        $this->validate($request, [
            'registration_status' => 'required|in:open,filtered,closed',
            'cloud_storage' => 'required',
            'activitypub_enabled' => 'required',
            'authorized_fetch' => 'required',
            'account_migration' => 'required',
            'mobile_apis' => 'required',
            'stories' => 'required',
            'instagram_import' => 'required',
            'autospam_enabled' => 'required',
        ]);

        $regStatus = $request->input('registration_status');
        ConfigCacheService::put('pixelfed.open_registration', $regStatus === 'open');
        ConfigCacheService::put('instance.curated_registration.enabled', $regStatus === 'filtered');
        $cloudStorage = $request->boolean('cloud_storage');
        if ($cloudStorage !== (bool) config_cache('pixelfed.cloud_storage')) {
            if (! $cloudStorage) {
                ConfigCacheService::put('pixelfed.cloud_storage', false);
            } else {
                $cloud_disk = config('filesystems.cloud');
                $cloud_ready = ! empty(config('filesystems.disks.'.$cloud_disk.'.key')) && ! empty(config('filesystems.disks.'.$cloud_disk.'.secret'));
                if (! $cloud_ready) {
                    return redirect()->back()->withErrors(['cloud_storage' => 'Must configure cloud storage before enabling!']);
                } else {
                    ConfigCacheService::put('pixelfed.cloud_storage', true);
                }
            }
        }
        ConfigCacheService::put('federation.activitypub.authorized_fetch', $request->boolean('authorized_fetch'));
        ConfigCacheService::put('federation.activitypub.enabled', $request->boolean('activitypub_enabled'));
        ConfigCacheService::put('federation.migration', $request->boolean('account_migration'));
        ConfigCacheService::put('pixelfed.oauth_enabled', $request->boolean('mobile_apis'));
        ConfigCacheService::put('instance.stories.enabled', $request->boolean('stories'));
        ConfigCacheService::put('pixelfed.import.instagram.enabled', $request->boolean('instagram_import'));
        ConfigCacheService::put('pixelfed.bouncer.enabled', $request->boolean('autospam_enabled'));

        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Cache::forget('api:v1:instance-data:contact');
        Config::refresh();

        return $request->all();
    }

    public function settingsApiUpdateLandingType($request)
    {
        $this->validate($request, [
            'current_admin' => 'required',
            'show_directory' => 'required',
            'show_explore' => 'required',
        ]);

        ConfigCacheService::put('instance.admin.pid', $request->input('current_admin'));
        ConfigCacheService::put('instance.landing.show_directory', $request->boolean('show_directory'));
        ConfigCacheService::put('instance.landing.show_explore', $request->boolean('show_explore'));

        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Cache::forget('api:v1:instance-data:contact');
        Config::refresh();

        return $request->all();
    }

    public function settingsApiUpdateMediaType($request)
    {
        $this->validate($request, [
            'image_quality' => 'required|integer|min:1|max:100',
            'max_album_length' => 'required|integer|min:1|max:20',
            'max_photo_size' => 'required|integer|min:100|max:50000',
            'media_types' => 'required',
            'optimize_image' => 'required',
            'optimize_video' => 'required',
        ]);

        $mediaTypes = $request->input('media_types');
        $mediaArray = explode(',', $mediaTypes);
        foreach ($mediaArray as $mediaType) {
            if (! in_array($mediaType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4'])) {
                return redirect()->back()->withErrors(['media_types' => 'Invalid media type']);
            }
        }

        ConfigCacheService::put('pixelfed.media_types', $request->input('media_types'));
        ConfigCacheService::put('pixelfed.image_quality', $request->input('image_quality'));
        ConfigCacheService::put('pixelfed.max_album_length', $request->input('max_album_length'));
        ConfigCacheService::put('pixelfed.max_photo_size', $request->input('max_photo_size'));
        ConfigCacheService::put('pixelfed.optimize_image', $request->boolean('optimize_image'));
        ConfigCacheService::put('pixelfed.optimize_video', $request->boolean('optimize_video'));

        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Cache::forget('api:v1:instance-data:contact');
        Config::refresh();

        return $request->all();
    }

    public function settingsApiUpdateBrandingType($request)
    {
        $this->validate($request, [
            'name' => 'required',
            'short_description' => 'required',
            'long_description' => 'required',
        ]);

        ConfigCacheService::put('app.name', $request->input('name'));
        ConfigCacheService::put('app.short_description', $request->input('short_description'));
        ConfigCacheService::put('app.description', $request->input('long_description'));

        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Cache::forget('api:v1:instance-data:contact');
        Config::refresh();

        return $request->all();
    }

    public function settingsApiUpdatePostsType($request)
    {
        $this->validate($request, [
            'max_caption_length' => 'required|integer|min:5|max:10000',
            'max_altext_length' => 'required|integer|min:5|max:40000',
        ]);

        ConfigCacheService::put('pixelfed.max_caption_length', $request->input('max_caption_length'));
        ConfigCacheService::put('pixelfed.max_altext_length', $request->input('max_altext_length'));
        $res = [
            'max_caption_length' => $request->input('max_caption_length'),
            'max_altext_length' => $request->input('max_altext_length'),
        ];
        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Config::refresh();

        return $res;
    }

    public function settingsApiUpdatePlatformType($request)
    {
        $this->validate($request, [
            'allow_app_registration' => 'required',
            'app_registration_rate_limit_attempts' => 'required|integer|min:1',
            'app_registration_rate_limit_decay' => 'required|integer|min:1',
            'app_registration_confirm_rate_limit_attempts' => 'required|integer|min:1',
            'app_registration_confirm_rate_limit_decay' => 'required|integer|min:1',
            'allow_post_embeds' => 'required',
            'allow_profile_embeds' => 'required',
            'captcha_enabled' => 'required',
            'captcha_on_login' => 'required_if_accepted:captcha_enabled',
            'captcha_on_register' => 'required_if_accepted:captcha_enabled',
            'captcha_secret' => 'required_if_accepted:captcha_enabled',
            'captcha_sitekey' => 'required_if_accepted:captcha_enabled',
            'custom_emoji_enabled' => 'required',
        ]);

        ConfigCacheService::put('pixelfed.allow_app_registration', $request->boolean('allow_app_registration'));
        ConfigCacheService::put('pixelfed.app_registration_rate_limit_attempts', $request->input('app_registration_rate_limit_attempts'));
        ConfigCacheService::put('pixelfed.app_registration_rate_limit_decay', $request->input('app_registration_rate_limit_decay'));
        ConfigCacheService::put('pixelfed.app_registration_confirm_rate_limit_attempts', $request->input('app_registration_confirm_rate_limit_attempts'));
        ConfigCacheService::put('pixelfed.app_registration_confirm_rate_limit_decay', $request->input('app_registration_confirm_rate_limit_decay'));
        ConfigCacheService::put('instance.embed.post', $request->boolean('allow_post_embeds'));
        ConfigCacheService::put('instance.embed.profile', $request->boolean('allow_profile_embeds'));
        ConfigCacheService::put('federation.custom_emoji.enabled', $request->boolean('custom_emoji_enabled'));
        $captcha = $request->boolean('captcha_enabled');
        if ($captcha) {
            $secret = $request->input('captcha_secret');
            $sitekey = $request->input('captcha_sitekey');
            if (config_cache('captcha.secret') != $secret && strpos($secret, '*') === false) {
                ConfigCacheService::put('captcha.secret', $secret);
            }
            if (config_cache('captcha.sitekey') != $sitekey && strpos($sitekey, '*') === false) {
                ConfigCacheService::put('captcha.sitekey', $sitekey);
            }
            ConfigCacheService::put('captcha.active.login', $request->boolean('captcha_on_login'));
            ConfigCacheService::put('captcha.active.register', $request->boolean('captcha_on_register'));
            ConfigCacheService::put('captcha.triggers.login.enabled', $request->boolean('captcha_on_login'));
            ConfigCacheService::put('captcha.enabled', true);
        } else {
            ConfigCacheService::put('captcha.enabled', false);
        }
        $res = [
            'allow_app_registration' => $request->boolean('allow_app_registration'),
            'app_registration_rate_limit_attempts' => $request->input('app_registration_rate_limit_attempts'),
            'app_registration_rate_limit_decay' => $request->input('app_registration_rate_limit_decay'),
            'app_registration_confirm_rate_limit_attempts' => $request->input('app_registration_confirm_rate_limit_attempts'),
            'app_registration_confirm_rate_limit_decay' => $request->input('app_registration_confirm_rate_limit_decay'),
            'allow_post_embeds' => $request->boolean('allow_post_embeds'),
            'allow_profile_embeds' => $request->boolean('allow_profile_embeds'),
            'captcha_enabled' => $request->boolean('captcha_enabled'),
            'captcha_on_login' => $request->boolean('captcha_on_login'),
            'captcha_on_register' => $request->boolean('captcha_on_register'),
            'captcha_secret' => $request->input('captcha_secret'),
            'captcha_sitekey' => $request->input('captcha_sitekey'),
            'custom_emoji_enabled' => $request->boolean('custom_emoji_enabled'),
        ];
        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Config::refresh();

        return $res;
    }

    public function settingsApiUpdateUsersType($request)
    {
        $this->validate($request, [
            'require_email_verification' => 'required',
            'enforce_account_limit' => 'required',
            'max_account_size' => 'required|integer|min:50000',
            'admin_autofollow' => 'required',
            'admin_autofollow_accounts' => 'sometimes',
            'max_user_blocks' => 'required|integer|min:0|max:5000',
            'max_user_mutes' => 'required|integer|min:0|max:5000',
            'max_domain_blocks' => 'required|integer|min:0|max:5000',
        ]);

        $adminAutofollow = $request->boolean('admin_autofollow');
        $adminAutofollowAccounts = $request->input('admin_autofollow_accounts');
        if ($adminAutofollow) {
            if ($request->filled('admin_autofollow_accounts')) {
                $names = [];
                $existing = config_cache('account.autofollow_usernames');
                if ($existing) {
                    $names = explode(',', $existing);
                    foreach (array_map('strtolower', $adminAutofollowAccounts) as $afc) {
                        if (in_array(strtolower($afc), array_map('strtolower', $names))) {
                            continue;
                        }
                        $names[] = $afc;
                    }
                } else {
                    $names = $adminAutofollowAccounts;
                }
                if (! $names || count($names) == 0) {
                    return response()->json(['message' => 'You need to assign autofollow accounts before you can enable it.'], 400);
                }
                if (count($names) > 5) {
                    return response()->json(['message' => 'You can only add up to 5 accounts to be autofollowed.'.json_encode($names)], 400);
                }
                $autofollows = User::whereIn('username', $names)->whereNull('status')->pluck('username');
                $adminAutofollowAccounts = $autofollows->implode(',');
                ConfigCacheService::put('account.autofollow_usernames', $adminAutofollowAccounts);
            } else {
                return response()->json(['message' => 'You need to assign autofollow accounts before you can enable it.'], 400);
            }
        }

        ConfigCacheService::put('pixelfed.enforce_email_verification', $request->boolean('require_email_verification'));
        ConfigCacheService::put('pixelfed.enforce_account_limit', $request->boolean('enforce_account_limit'));
        ConfigCacheService::put('pixelfed.max_account_size', $request->input('max_account_size'));
        ConfigCacheService::put('account.autofollow', $request->boolean('admin_autofollow'));
        ConfigCacheService::put('instance.user_filters.max_user_blocks', (int) $request->input('max_user_blocks'));
        ConfigCacheService::put('instance.user_filters.max_user_mutes', (int) $request->input('max_user_mutes'));
        ConfigCacheService::put('instance.user_filters.max_domain_blocks', (int) $request->input('max_domain_blocks'));
        $res = [
            'require_email_verification' => $request->boolean('require_email_verification'),
            'enforce_account_limit' => $request->boolean('enforce_account_limit'),
            'admin_autofollow' => $request->boolean('admin_autofollow'),
            'admin_autofollow_accounts' => $adminAutofollowAccounts,
            'max_user_blocks' => $request->input('max_user_blocks'),
            'max_user_mutes' => $request->input('max_user_mutes'),
            'max_domain_blocks' => $request->input('max_domain_blocks'),
        ];
        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Config::refresh();

        return $res;
    }

    public function settingsApiUpdateStorageType($request)
    {
        $this->validate($request, [
            'primary_disk' => 'required|in:local,cloud',
            'update_disk' => 'sometimes',
            'disk_config' => 'required_if_accepted:update_disk',
            'disk_config.driver' => 'required|in:s3,spaces',
            'disk_config.key' => 'required',
            'disk_config.secret' => 'required',
            'disk_config.region' => 'required',
            'disk_config.bucket' => 'required',
            'disk_config.visibility' => 'required',
            'disk_config.endpoint' => 'required',
            'disk_config.url' => 'nullable',
        ]);

        ConfigCacheService::put('pixelfed.cloud_storage', $request->input('primary_disk') === 'cloud');
        $res = [
            'primary_disk' => $request->input('primary_disk'),
        ];
        if ($request->has('update_disk')) {
            $res['disk_config'] = $request->input('disk_config');
            $changes = [];
            $dkey = $request->input('disk_config.driver') === 's3' ? 'filesystems.disks.s3.' : 'filesystems.disks.spaces.';
            $key = $request->input('disk_config.key');
            $ckey = null;
            $secret = $request->input('disk_config.secret');
            $csecret = null;
            $region = $request->input('disk_config.region');
            $bucket = $request->input('disk_config.bucket');
            $visibility = $request->input('disk_config.visibility');
            $url = $request->input('disk_config.url');
            $endpoint = $request->input('disk_config.endpoint');
            if (strpos($key, '*') === false && $key != config_cache($dkey.'key')) {
                array_push($changes, 'key');
            } else {
                $ckey = config_cache($dkey.'key');
            }
            if (strpos($secret, '*') === false && $secret != config_cache($dkey.'secret')) {
                array_push($changes, 'secret');
            } else {
                $csecret = config_cache($dkey.'secret');
            }
            if ($region != config_cache($dkey.'region')) {
                array_push($changes, 'region');
            }
            if ($bucket != config_cache($dkey.'bucket')) {
                array_push($changes, 'bucket');
            }
            if ($visibility != config_cache($dkey.'visibility')) {
                array_push($changes, 'visibility');
            }
            if ($url != config_cache($dkey.'url')) {
                array_push($changes, 'url');
            }
            if ($endpoint != config_cache($dkey.'endpoint')) {
                array_push($changes, 'endpoint');
            }

            if ($changes && count($changes)) {
                $isValid = FilesystemService::getVerifyCredentials(
                    $ckey ?? $key,
                    $csecret ?? $secret,
                    $region,
                    $bucket,
                    $endpoint,
                );
                if (! $isValid) {
                    return response()->json(['error' => true, 's3_vce' => true, 'message' => "<div class='border border-danger text-danger p-3 font-weight-bold rounded-lg'>The S3/Spaces credentials you provided are invalid, or the bucket does not have the proper permissions.</div><br/>Please check all fields and try again.<br/><br/><strong>Any cloud storage configuration changes you made have NOT been saved due to invalid credentials.</strong>"], 400);
                }
            }
            $res['changes'] = json_encode($changes);
        }
        Cache::forget('api:v1:instance-data:rules');
        Cache::forget('api:v1:instance-data-response-v1');
        Cache::forget('api:v2:instance-data-response-v2');
        Config::refresh();

        return $res;
    }
}
