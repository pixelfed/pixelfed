<?php

namespace App\Jobs\StatusPipeline;

use App\Hashtag;
use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Jobs\MentionPipeline\MentionPipeline;
use App\Mention;
use App\Profile;
use App\Services\AdminShadowFilterService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use App\Status;
use App\StatusHashtag;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use App\Util\Sentiment\Bouncer;
use Cache;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StatusEntityLexer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    protected $entities;

    protected $autolink;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;

        // Verify status exists
        if (!$status) {
            Log::info("StatusEntityLexer: Status no longer exists, skipping job");
            return;
        }

        // Verify status has a profile
        if (!$status->profile_id) {
            Log::info("StatusEntityLexer: Status {$status->id} has no profile_id, skipping job");
            return;
        }

        $profile = $status->profile;
        if (!$profile) {
            Log::info("StatusEntityLexer: Profile no longer exists for status {$status->id}, skipping job");
            return;
        }

        if (in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
            $profile->status_count = $profile->status_count + 1;
            $profile->save();
        }

        if ($profile->no_autolink == false) {
            $this->parseEntities();
        }
    }

    public function parseEntities()
    {
        $this->extractEntities();
    }

    public function extractEntities()
    {
        $this->entities = Extractor::create()->extract($this->status->caption);
        $this->autolinkStatus();
    }

    public function autolinkStatus()
    {
        $this->autolink = Autolink::create()->autolink($this->status->caption);
        $this->storeEntities();
    }

    public function storeEntities()
    {
        $this->storeHashtags();
    }

    public function storeHashtags()
    {
        $tags = array_unique($this->entities['hashtags']);
        $status = $this->status;

        foreach ($tags as $tag) {
            if (mb_strlen($tag) > 124) {
                continue;
            }
            DB::transaction(function () use ($status, $tag) {
                $slug = str_slug($tag, '-', false);

                $hashtag = Hashtag::firstOrCreate([
                    'slug' => $slug,
                ], [
                    'name' => $tag,
                ]);

                StatusHashtag::firstOrCreate(
                    [
                        'status_id' => $status->id,
                        'hashtag_id' => $hashtag->id,
                        'profile_id' => $status->profile_id,
                        'status_visibility' => $status->visibility,
                    ]
                );
            });
        }
        $this->storeMentions();
    }

    public function storeMentions()
    {
        $mentions = array_unique($this->entities['mentions']);
        $status = $this->status;

        foreach ($mentions as $mention) {
            $mentioned = Profile::whereUsername($mention)->first();

            if (empty($mentioned) || ! isset($mentioned->id)) {
                continue;
            }
            $blocks = UserFilterService::blocks($mentioned->id);
            if ($blocks && in_array($status->profile_id, $blocks)) {
                continue;
            }

            DB::transaction(function () use ($status, $mentioned) {
                $m = new Mention;
                $m->status_id = $status->id;
                $m->profile_id = $mentioned->id;
                $m->save();

                MentionPipeline::dispatch($status, $m);
            });
        }
        $this->fanout();
    }

    public function fanout()
    {
        $status = $this->status;
        StatusService::refresh($status->id);

        if (config('exp.cached_home_timeline')) {
            if ($status->in_reply_to_id === null &&
                in_array($status->scope, ['public', 'unlisted', 'private'])
            ) {
                FeedInsertPipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');
            }
        }
        $this->deliver();
    }

    public function deliver()
    {
        $status = $this->status;
        $types = [
            'photo',
            'photo:album',
            'video',
            'video:album',
            'photo:video:album',
        ];

        if ((bool) config_cache('pixelfed.bouncer.enabled')) {
            Bouncer::get($status);
        }

        Cache::forget('pf:atom:user-feed:by-id:'.$status->profile_id);
        $hideNsfw = config('instance.hide_nsfw_on_public_feeds');
        if ($status->uri == null &&
            $status->scope == 'public' &&
            in_array($status->type, $types) &&
            $status->in_reply_to_id === null &&
            $status->reblog_of_id === null &&
            ($hideNsfw ? $status->is_nsfw == false : true)
        ) {
            if (AdminShadowFilterService::canAddToPublicFeedByProfileId($status->profile_id)) {
                PublicTimelineService::add($status->id);
            }
        }

        if ((bool) config_cache('federation.activitypub.enabled') == true && config('app.env') == 'production') {
            StatusActivityPubDeliver::dispatch($status);
        }
    }
}
