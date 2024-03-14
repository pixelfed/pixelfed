<?php

namespace App\Http\Controllers\Admin;

use App\Models\ConfigCache;
use App\Models\InstanceActor;
use App\Page;
use App\Profile;
use App\Services\AccountService;
use App\Services\ConfigCacheService;
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
            'regs' => 'nullable|in:open,filtered,closed',
            'account_migration' => 'nullable',
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
                ! file_exists(storage_path('oauth-private.key'))
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
}
