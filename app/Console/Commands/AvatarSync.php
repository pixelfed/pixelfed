<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Avatar;
use App\Profile;
use App\Jobs\AvatarPipeline\RemoteAvatarFetch;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AvatarSync extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'avatars:sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Perform actions on avatars';

	public $found = 0;
	public $notFetched = 0;
	public $fixed = 0;

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
	 * @return int
	 */
	public function handle()
	{
		$this->info('Welcome to the avatar sync manager');

		$actions = [
			'Analyze',
			'Full Analyze',
			'Fetch - Fetch missing remote avatars',
			'Fix - Fix remote accounts without avatar record',
			'Sync - Store latest remote avatars',
		];

		$name = $this->choice(
			'Select an action',
			$actions,
			0,
			1,
			false
		);

		$this->info('Selected: ' . $name);

		switch($name) {
			case $actions[0]:
				$this->analyze();
			break;

			case $actions[1]:
				$this->fullAnalyze();
			break;

			case $actions[2]:
				$this->fetch();
			break;

			case $actions[3]:
				$this->fix();
			break;

			case $actions[4]:
				$this->sync();
			break;
		}
	    return Command::SUCCESS;
	}

	protected function incr($name)
	{
		switch($name) {
			case 'found':
				$this->found = $this->found + 1;
			break;

			case 'notFetched':
				$this->notFetched = $this->notFetched + 1;
			break;

			case 'fixed':
				$this->fixed++;
			break;
		}
	}

	protected function analyze()
	{
		$count = Avatar::whereIsRemote(true)->whereNull('cdn_url')->count();
		$this->info('Found ' . $count . ' profiles with blank avatars.');
		$this->line(' ');
		$this->comment('We suggest running php artisan avatars:sync again and selecting the sync option');
		$this->line(' ');
	}

	protected function fullAnalyze()
	{
		$count = Profile::count();
		$bar = $this->output->createProgressBar($count);
		$bar->start();

		Profile::chunk(5000, function($profiles) use ($bar) {
			foreach($profiles as $profile) {
				if($profile->domain == null) {
					$bar->advance();
					continue;
				}
				$avatar = Avatar::whereProfileId($profile->id)->first();
				if(!$avatar || $avatar->cdn_url == null) {
					$this->incr('notFetched');
				}
				$this->incr('found');
				$bar->advance();
			}
		});

		$this->line(' ');
		$this->line(' ');
		$this->info('Found ' . $this->found . ' remote accounts');
		$this->info('Found ' . $this->notFetched . ' remote avatars to fetch');
	}

	protected function fetch()
	{
		$this->info('Fetching ....');
		Avatar::whereIsRemote(true)
			->whereNull('cdn_url')
			// ->with('profile')
			->chunk(10, function($avatars) {
				foreach($avatars as $avatar) {
					if(!$avatar || !$avatar->profile) {
						continue;
					}
					$url = $avatar->profile->remote_url;
					if(!$url || !Helpers::validateUrl($url)) {
						continue;
					}
					try {
						$res = Helpers::fetchFromUrl($url);
						if(
							!is_array($res) ||
							!isset($res['@context']) ||
							!isset($res['icon']) ||
							!isset($res['icon']['type']) ||
							!isset($res['icon']['url']) ||
							!Str::endsWith($res['icon']['url'], ['.png', '.jpg', '.jpeg'])
						) {
							continue;
						}
					} catch (\GuzzleHttp\Exception\RequestException $e) {
						continue;
					} catch(\Illuminate\Http\Client\ConnectionException $e) {
						continue;
					}
					$avatar->remote_url = $res['icon']['url'];
					$avatar->save();
					RemoteAvatarFetch::dispatch($avatar->profile);
				}
		});
	}

	protected function fix()
	{
		Profile::chunk(5000, function($profiles) {
			foreach($profiles as $profile) {
				if($profile->domain == null || $profile->private_key) {
					continue;
				}
				$avatar = Avatar::whereProfileId($profile->id)->first();
				if($avatar) {
					continue;
				}
				$avatar = new Avatar;
				$avatar->is_remote = true;
				$avatar->profile_id = $profile->id;
				$avatar->save();
				$this->incr('fixed');
			}
		});
		$this->line(' ');
		$this->line(' ');
		$this->info('Fixed ' . $this->fixed . ' accounts with a blank avatar');
	}

	protected function sync()
	{
		Avatar::whereIsRemote(true)
			->with('profile')
			->chunk(10, function($avatars) {
				foreach($avatars as $avatar) {
					RemoteAvatarFetch::dispatch($avatar->profile);
				}
		});
	}
	}
