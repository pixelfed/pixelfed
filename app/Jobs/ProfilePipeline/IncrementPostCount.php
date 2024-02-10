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

class IncrementPostCount implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $id;

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
        // deprecated
        return;
	}
}
