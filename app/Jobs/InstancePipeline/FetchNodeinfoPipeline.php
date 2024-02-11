<?php

namespace App\Jobs\InstancePipeline;

use App\Instance;
use App\Profile;
use App\Services\NodeinfoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchNodeinfoPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 14400;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->instance->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $instance = $this->instance;

        if ($instance->nodeinfo_last_fetched &&
            $instance->nodeinfo_last_fetched->gt(now()->subHours(12)) ||
            $instance->delivery_timeout &&
            $instance->delivery_next_after->gt(now())
        ) {
            return;
        }

        $ni = NodeinfoService::get($instance->domain);
        $instance->last_crawled_at = now();
        if ($ni) {
            if (isset($ni['software']) && is_array($ni['software']) && isset($ni['software']['name'])) {
                $software = $ni['software']['name'];
                $instance->software = strtolower(strip_tags($software));
                $instance->user_count = Profile::whereDomain($instance->domain)->count();
                $instance->nodeinfo_last_fetched = now();
                $instance->save();
            }
        } else {
            $instance->delivery_timeout = 1;
            $instance->delivery_next_after = now()->addHours(14);
            $instance->save();
        }
    }
}
