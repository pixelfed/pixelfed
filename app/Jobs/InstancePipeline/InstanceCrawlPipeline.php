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

class InstanceCrawlPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		Instance::whereNull('last_crawled_at')->whereNull('software')->chunk(50, function($instances) {
			foreach($instances as $instance) {
				FetchNodeinfoPipeline::dispatch($instance)->onQueue('low');
			}
		});
	}
}
