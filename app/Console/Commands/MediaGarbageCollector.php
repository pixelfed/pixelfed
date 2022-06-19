<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Media, Status};
use Carbon\Carbon;
use App\Services\MediaStorageService;

class MediaGarbageCollector extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'media:gc';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Delete media uploads not attached to any active statuses';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$limit = 20000;

		$gc = Media::whereNull('status_id')
		->where('created_at', '<', Carbon::now()->subHours(12)->toDateTimeString())
		->orderBy('created_at','asc')
		->take($limit)
		->get();

		$bar = $this->output->createProgressBar($gc->count());
		$bar->start();
		foreach($gc as $media) {
			MediaStorageService::delete($media, true);
			$bar->advance();
		}
		$bar->finish();
		$this->line('');
	}
}
