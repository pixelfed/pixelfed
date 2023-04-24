<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Media;
use League\Flysystem\MountManager;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\MediaPipeline\MediaFixLocalFilesystemCleanupPipeline;

class FixMediaDriver extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'media:fix-nonlocal-driver';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Fix filesystem when FILESYSTEM_DRIVER not set to local';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle()
	{
		if(config('filesystems.default') !== 'local') {
			$this->error('Invalid default filesystem, set FILESYSTEM_DRIVER=local to proceed');
			return Command::SUCCESS;
		}

		if(config_cache('pixelfed.cloud_storage') == false) {
			$this->error('Cloud storage not enabled, exiting...');
			return Command::SUCCESS;
		}

		$this->info('       ____  _           ______         __  ');
		$this->info('      / __ \(_)  _____  / / __/__  ____/ /  ');
		$this->info('     / /_/ / / |/_/ _ \/ / /_/ _ \/ __  /   ');
		$this->info('    / ____/ />  </  __/ / __/  __/ /_/ /    ');
		$this->info('   /_/   /_/_/|_|\___/_/_/  \___/\__,_/     ');
		$this->info(' ');
		$this->info('   Media Filesystem Fix');
		$this->info('   =====================');
		$this->info('   Fix media that was created when FILESYSTEM_DRIVER=local');
		$this->info('   was not properly set. This command will fix media urls');
		$this->info('   and optionally optimize/generate thumbnails when applicable,');
		$this->info('   clean up temporary local media files and clear the app cache');
		$this->info('   to fix media paths/urls.');
		$this->info(' ');
		$this->error('   Remember, FILESYSTEM_DRIVER=local must remain set or you will break things!');

		if(!$this->confirm('Are you sure you want to perform this command?')) {
			$this->info('Exiting...');
			return Command::SUCCESS;
		}

		$optimize = $this->choice(
			'Do you want to optimize media and generate thumbnails? This will store s3 locally and re-upload optimized versions.',
			['no', 'yes'],
			1
		);

		$cloud = Storage::disk(config('filesystems.cloud'));
		$mountManager = new MountManager([
			's3' => $cloud->getDriver(),
			'local' => Storage::disk('local')->getDriver(),
		]);

		$this->info('Fixing media, this may take a while...');
		$this->line(' ');
		$bar = $this->output->createProgressBar(Media::whereNotNull('status_id')->whereNull('cdn_url')->count());
		$bar->start();

		foreach(Media::whereNotNull('status_id')->whereNull('cdn_url')->lazyById(20) as $media) {
			if($cloud->exists($media->media_path)) {
				if($optimize === 'yes') {
					$mountManager->copy(
						's3://' . $media->media_path,
						'local://' . $media->media_path
					);
					sleep(1);
					if(empty($media->original_sha256)) {
						$hash = \hash_file('sha256', Storage::disk('local')->path($media->media_path));
						$media->original_sha256 = $hash;
						$media->save();
						sleep(1);
					}
					if(
						$media->mime &&
						in_array($media->mime, [
							'image/jpeg',
							'image/png',
							'image/webp'
						])
					) {
						ImageOptimize::dispatch($media);
						sleep(3);
					}
				} else {
					$media->cdn_url = $cloud->url($media->media_path);
					$media->save();
				}
			}
			$bar->advance();
		}

		$bar->finish();
		$this->line(' ');
		$this->line(' ');

		$this->callSilently('cache:clear');

		$this->info('Successfully fixed media paths and cleared cached!');

		if($optimize === 'yes') {
			MediaFixLocalFilesystemCleanupPipeline::dispatch()->delay(now()->addMinutes(15))->onQueue('default');
			$this->line(' ');
			$this->info('A cleanup job has been dispatched to delete media stored locally, it may take a few minutes to process!');
		}

		$this->line(' ');
		return Command::SUCCESS;
	}
}
