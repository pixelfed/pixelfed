<?php

namespace App\Jobs\StatusPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AccountService;
use App\Services\CustomEmojiService;
use App\Services\StatusService;
use App\Jobs\MentionPipeline\MentionPipeline;
use App\Mention;
use App\Hashtag;
use App\StatusHashtag;
use App\Services\TrendingHashtagService;
use App\Util\ActivityPub\Helpers;

class StatusTagsPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $activity;
    protected $status;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($activity, $status)
    {
        $this->activity = $activity;
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $res = $this->activity;
        $status = $this->status;

        if(isset($res['tag']['type'], $res['tag']['name'])) {
            $res['tag'] = [$res['tag']];
        }

        $tags = collect($res['tag']);

        // Emoji
        $tags->filter(function($tag) {
            return $tag && isset($tag['id'], $tag['icon'], $tag['name'], $tag['type']) && $tag['type'] == 'Emoji';
        })
        ->map(function($tag) {
            CustomEmojiService::import($tag['id'], $this->status->id);
        });

        // Hashtags
        $tags->filter(function($tag) {
            return $tag && $tag['type'] == 'Hashtag' && isset($tag['href'], $tag['name']);
        })
        ->map(function($tag) use($status) {
            $name = substr($tag['name'], 0, 1) == '#' ?
                substr($tag['name'], 1) : $tag['name'];

            $banned = TrendingHashtagService::getBannedHashtagNames();

            if(count($banned)) {
                if(in_array(strtolower($name), array_map('strtolower', $banned))) {
                    return;
                }
            }

            if(config('database.default') === 'pgsql') {
                $hashtag = Hashtag::where('name', 'ilike', $name)
                    ->orWhere('slug', 'ilike', str_slug($name, '-', false))
                    ->first();

                if(!$hashtag) {
                    $hashtag = Hashtag::updateOrCreate([
                        'slug' => str_slug($name, '-', false),
                        'name' => $name
                    ]);
                }
            } else {
                $hashtag = Hashtag::updateOrCreate([
                    'slug' => str_slug($name, '-', false),
                    'name' => $name
                ]);
            }

            StatusHashtag::firstOrCreate([
                'status_id' => $status->id,
                'hashtag_id' => $hashtag->id,
                'profile_id' => $status->profile_id,
                'status_visibility' => $status->scope
            ]);
        });

        // Mentions
        $tags->filter(function($tag) {
            return $tag &&
                $tag['type'] == 'Mention' &&
                isset($tag['href']) &&
                substr($tag['href'], 0, 8) === 'https://';
        })
        ->map(function($tag) use($status) {
            if(Helpers::validateLocalUrl($tag['href'])) {
                $parts = explode('/', $tag['href']);
                if(!$parts) {
                    return;
                }
                $pid = AccountService::usernameToId(end($parts));
                if(!$pid) {
                    return;
                }
            } else {
                $acct = Helpers::profileFetch($tag['href']);
                if(!$acct) {
                    return;
                }
                $pid = $acct->id;
            }
            $mention = new Mention;
            $mention->status_id = $status->id;
            $mention->profile_id = $pid;
            $mention->save();
            MentionPipeline::dispatch($status, $mention);
        });

        StatusService::refresh($status->id);
    }
}
