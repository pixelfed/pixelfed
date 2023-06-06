<?php

namespace App\Jobs\ProfilePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Avatar;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use Cache;
use Purify;
use App\Jobs\AvatarPipeline\RemoteAvatarFetchFromUrl;
use App\Util\Lexer\Autolink;

class HandleUpdateActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $payload = $this->payload;

        if(empty($payload) || !isset($payload['actor'])) {
            return;
        }

        $profile = Profile::whereRemoteUrl($payload['actor'])->first();

        if(!$profile || $profile->domain === null || $profile->private_key) {
            return;
        }

        if($profile->sharedInbox == null || $profile->sharedInbox != $payload['object']['endpoints']['sharedInbox']) {
            $profile->sharedInbox = $payload['object']['endpoints']['sharedInbox'];
        }

        if($profile->public_key !== $payload['object']['publicKey']['publicKeyPem']) {
            $profile->public_key = $payload['object']['publicKey']['publicKeyPem'];
        }

        if($profile->bio !== $payload['object']['summary']) {
            $len = strlen(strip_tags($payload['object']['summary']));
            if($len) {
                if($len > 500) {
                    $updated = strip_tags($payload['object']['summary']);
                    $updated = substr($updated, 0, config('pixelfed.max_bio_length'));
                    $profile->bio = Autolink::create()->autolink($updated);
                } else {
                    $profile->bio = Purify::clean($payload['object']['summary']);
                }
            } else {
                $profile->bio = null;
            }
        }

        if($profile->name !== $payload['object']['name']) {
            $profile->name = Purify::clean(substr($payload['object']['name'], 0, config('pixelfed.max_name_length')));
        }

        if($profile->isDirty()) {
            $profile->save();
        }

        if(isset($payload['object']['icon']) && isset($payload['object']['icon']['url'])) {
            RemoteAvatarFetch::dispatch($profile, $payload['object']['icon']['url'])->onQueue('low');
        } else {
            $profile->avatar->update(['remote_url' => null]);
            Cache::forget('avatar:' . $profile->id);
        }

        return;
    }
}
