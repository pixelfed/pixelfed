<?php

namespace App\Jobs\StoryPipeline;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Story;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Util\ActivityPub\Helpers;

class StoryRotateMedia implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $story;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(Story $story)
	{
		$this->story = $story;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$story = $this->story;

		if($story->local == false) {
			return;
		}

		$paths = explode('/', $story->path);
		$name = array_pop($paths);

		$oldPath = $story->path;
		$ext = pathinfo($name, PATHINFO_EXTENSION);
		$new = Str::random(13) . '_' . Str::random(24) . '_' . Str::random(3) . '.' . $ext;
		array_push($paths, $new);
		$newPath = implode('/', $paths);

		if(Storage::exists($oldPath)) {
			Storage::copy($oldPath, $newPath);
			$story->path = $newPath;
			$story->bearcap_token = null;
			$story->save();
			Storage::delete($oldPath);
		}
	}
}
