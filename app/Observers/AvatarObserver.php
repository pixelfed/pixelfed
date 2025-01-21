<?php

namespace App\Observers;

use App\Avatar;
use App\Services\AccountService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the avatar "created" event.
     *
     * @return void
     */
    public function created(Avatar $avatar)
    {
        //
    }

    /**
     * Handle the avatar "updated" event.
     *
     * @return void
     */
    public function updated(Avatar $avatar)
    {
        AccountService::del($avatar->profile_id);
    }

    /**
     * Handle the avatar "deleted" event.
     *
     * @return void
     */
    public function deleted(Avatar $avatar)
    {
        //
    }

    /**
     * Handle the avatar "deleting" event.
     *
     * @return void
     */
    public function deleting(Avatar $avatar)
    {
        $path = storage_path('app/'.$avatar->media_path);
        if (is_file($path) &&
            $avatar->media_path != 'public/avatars/default.png' &&
            $avatar->media_path != 'public/avatars/default.jpg'
        ) {
            @unlink($path);
        }

        if ((bool) config_cache('pixelfed.cloud_storage')) {
            $disk = Storage::disk(config('filesystems.cloud'));
            $base = Str::startsWith($avatar->media_path, 'cache/avatars/');
            if ($base && $disk->exists($avatar->media_path)) {
                $disk->delete($avatar->media_path);
            }
        }
        AccountService::del($avatar->profile_id);
    }

    /**
     * Handle the avatar "restored" event.
     *
     * @return void
     */
    public function restored(Avatar $avatar)
    {
        //
    }

    /**
     * Handle the avatar "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Avatar $avatar)
    {
        //
    }
}
