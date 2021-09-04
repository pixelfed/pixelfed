<?php

namespace App\Jobs\InstancePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Instance;
use App\Profile;
use App\Services\NodeinfoService;

class FetchNodeinfoPipeline implements ShouldQueue
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
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$instance = $this->instance;

		$ni = NodeinfoService::get($instance->domain);
		if($ni) {
			if(isset($ni['software']) && is_array($ni['software']) && isset($ni['software']['name'])) {
				$software = $ni['software']['name'];
				$instance->software = strtolower(strip_tags($software));
				$instance->last_crawled_at = now();
				$instance->user_count = Profile::whereDomain($instance->domain)->count();
				$instance->save();
			}
		} else {
			$instance->user_count = Profile::whereDomain($instance->domain)->count();
			$instance->last_crawled_at = now();
			$instance->save();
		}
	}
}
