<?php

namespace App\Jobs\HomeFeedPipeline;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use App\Services\StatusService;
use App\Services\HomeTimelineService;

class FeedRemoveDomainPipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pid;
    protected $domain;

    public $timeout = 900;
    public $tries = 3;
    public $maxExceptions = 1;
    public $failOnTimeout = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'hts:feed:remove:domain:' . $this->pid . ':d-' . $this->domain;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hts:feed:remove:domain:{$this->pid}:d-{$this->domain}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($pid, $domain)
    {
        $this->pid = $pid;
        $this->domain = $domain;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if(!config('exp.cached_home_timeline')) {
            return;
        }

        if ($this->batch()->cancelled()) {
            return;
        }

        if(!$this->pid || !$this->domain) {
            return;
        }
        $domain = strtolower($this->domain);
        $pid = $this->pid;
        $posts = HomeTimelineService::get($pid, '0', '-1');

        foreach($posts as $post) {
            $status = StatusService::get($post, false);
            if(!$status || !isset($status['url'])) {
                HomeTimelineService::rem($pid, $post);
                continue;
            }
            $host = strtolower(parse_url($status['url'], PHP_URL_HOST));
            if($host === strtolower(config('pixelfed.domain.app')) || !$host) {
                continue;
            }
            if($host === $domain) {
                HomeTimelineService::rem($pid, $status['id']);
            }
        }
    }
}
