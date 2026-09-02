<?php

namespace App\Jobs\ModPipeline;

use App\Models\Profile;
use App\Models\Status;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

#[DeleteWhenMissingModels]
class HandleSpammerPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    public function handle()
    {
        $profile = $this->profile;

        $profile->unlisted = true;
        $profile->cw = true;
        $profile->no_autolink = true;
        $profile->save();

        Status::whereProfileId($profile->id)
            ->chunk(50, function ($statuses) {
                foreach ($statuses as $status) {
                    $status->is_nsfw = true;
                    $status->scope = $status->scope === 'public' ? 'unlisted' : $status->scope;
                    $status->visibility = $status->scope;
                    $status->save();
                    StatusService::del($status->id, true);
                }
            });

        Cache::forget('_api:statuses:recent_9:'.$profile->id);

        return 1;
    }
}
