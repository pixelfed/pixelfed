<?php

namespace App\Services;

use Cache;
use Storage;
use Illuminate\Support\Str;
use App\Avatar;
use App\Profile;
use App\Jobs\AvatarPipeline\AvatarStorageLargePurge;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToRetrieveMetadata;

class AvatarService
{
    public static function get($profile_id)
    {
        $exists = Cache::get('avatar:' . $profile_id);
        if($exists) {
            return $exists;
        }

        $profile = Profile::find($profile_id);
        if(!$profile) {
            return config('app.url') . '/storage/avatars/default.jpg';
        }
        return $profile->avatarUrl();
    }

    public static function disk()
    {
        $storage = [
            'cloud' => boolval(config_cache('pixelfed.cloud_storage')),
            'local' => boolval(config_cache('federation.avatars.store_local'))
        ];

        if(!$storage['cloud'] && !$storage['local']) {
            return false;
        }

        $driver = $storage['cloud'] == false ? 'local' : config('filesystems.cloud');
        $disk = Storage::disk($driver);

        return $disk;
    }

    public static function storage(Avatar $avatar)
    {
        $disk = self::disk();

        if(!$disk) {
            return;
        }

        $storage = [
            'cloud' => boolval(config_cache('pixelfed.cloud_storage')),
            'local' => boolval(config_cache('federation.avatars.store_local'))
        ];

        $base = ($storage['cloud'] == false ? 'public/cache/' : 'cache/') . 'avatars/';

        return $disk->allFiles($base . $avatar->profile_id);
    }

    public static function cleanup($avatar, $confirm = false)
    {
        if(!$avatar || !$confirm) {
            return;
        }

        if($avatar->cdn_url == null) {
            return;
        }

        $storage = [
            'cloud' => boolval(config_cache('pixelfed.cloud_storage')),
            'local' => boolval(config_cache('federation.avatars.store_local'))
        ];

        if(!$storage['cloud'] && !$storage['local']) {
            return;
        }

        $disk = self::disk();

        if(!$disk) {
            return;
        }

        $base = ($storage['cloud'] == false ? 'public/cache/' : 'cache/') . 'avatars/';

        try {
            $exists = $disk->directoryExists($base . $avatar->profile_id);
        } catch (
            UnableToRetrieveMetadata |
            UnableToCheckDirectoryExistence |
            Exception $e
        ) {
            return;
        }

        if(!$exists) {
            return;
        }

        $files = collect($disk->allFiles($base . $avatar->profile_id));

        if(!$files || !$files->count() || $files->count() === 1) {
            return;
        }

        if($files->count() > 5) {
            AvatarStorageLargePurge::dispatch($avatar)->onQueue('mmo');
            return;
        }

        $curFile = Str::of($avatar->cdn_url)->explode('/')->last();

        $files = $files->filter(function($f) use($curFile) {
            return !$curFile || !str_ends_with($f, $curFile);
        })->each(function($name) use($disk) {
            $disk->delete($name);
        });

        return;
    }
}
