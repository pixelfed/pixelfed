<?php

namespace App\Jobs\ProfilePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use App\Profile;
use App\Status;
use App\Services\AccountService;

class IncrementPostCount implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $id;

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
		return 'propipe:ipc:' . $this->id;
	}

	/**
	 * Get the middleware the job should pass through.
	 *
	 * @return array<int, object>
	 */
	public function middleware(): array
	{
		return [(new WithoutOverlapping("propipe:ipc:{$this->id}"))->shared()->dontRelease()];
	}

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$id = $this->id;

		$profile = Profile::find($id);

		if(!$profile) {
			return 1;
		}

		$profile->status_count = $profile->status_count + 1;
		$profile->last_status_at = now();
		$profile->save();
		AccountService::del($id);
		AccountService::get($id);

		return 1;
	}
}
