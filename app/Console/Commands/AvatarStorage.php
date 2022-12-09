<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Avatar;
use App\Profile;
use App\User;
use Cache;
use Storage;
use App\Services\AccountService;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Support\Str;
use App\Jobs\AvatarPipeline\RemoteAvatarFetch;

class AvatarStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatar:storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage avatar storage';

    public $found = 0;
    public $notFetched = 0;
    public $fixed = 0;
    public $missing = 0;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Pixelfed Avatar Storage Manager');
        $this->line(' ');
        $segments = [
            [
                'Local',
                Avatar::whereNull('is_remote')->count(),
                PrettyNumber::size(Avatar::whereNull('is_remote')->sum('size'))
            ],
            [
                'Remote',
                Avatar::whereIsRemote(true)->count(),
                PrettyNumber::size(Avatar::whereIsRemote(true)->sum('size'))
            ],
            [
                'Cached (CDN)',
                Avatar::whereNotNull('cdn_url')->count(),
                PrettyNumber::size(Avatar::whereNotNull('cdn_url')->sum('size'))
            ],
            [
                'Uncached',
                Avatar::whereNull('cdn_url')->count(),
                PrettyNumber::size(Avatar::whereNull('cdn_url')->sum('size'))
            ],
            [
                '------------',
                '----------',
                '-----'
            ],
            [
                'Total',
                Avatar::count(),
                PrettyNumber::size(Avatar::sum('size'))
            ],
        ];
        $this->table(
            ['Segment', 'Count', 'Space Used'],
            $segments
        );

        $this->line(' ');

        if(config_cache('pixelfed.cloud_storage')) {
            $this->info('✅ - Cloud storage configured');
            $this->line(' ');
        }

        if(config('instance.avatar.local_to_cloud')) {
            $this->info('✅ - Store avatars on cloud filesystem');
            $this->line(' ');
        }

        if(config_cache('pixelfed.cloud_storage') && config('instance.avatar.local_to_cloud')) {
            $disk = Storage::disk(config_cache('filesystems.cloud'));
            $exists = $disk->exists('cache/avatars/default.jpg');
            $state = $exists ? '✅' : '❌';
            $msg = $state . ' - Cloud default avatar exists';
            $this->info($msg);
        }

        $options = config_cache('pixelfed.cloud_storage') && config('instance.avatar.local_to_cloud') ?
            [
                'Cancel',
                'Upload default avatar to cloud',
                'Move local avatars to cloud',
                'Re-fetch remote avatars'
            ] : [
                'Cancel',
                'Re-fetch remote avatars'
        ];

        $this->missing = Profile::where('created_at', '<', now()->subDays(1))->doesntHave('avatar')->count();
        if($this->missing != 0) {
            $options[] = 'Fix missing avatars';
        }

        $choice = $this->choice(
            'Select action:',
            $options,
            0
        );

        return $this->handleChoice($choice);
    }

    protected function handleChoice($id)
    {
        switch ($id) {
            case 'Cancel':
                return;
            break;

            case 'Upload default avatar to cloud':
                return $this->uploadDefaultAvatar();
                break;

            case 'Move local avatars to cloud':
                return $this->uploadAvatarsToCloud();
                break;

            case 'Re-fetch remote avatars':
                return $this->refetchRemoteAvatars();
                break;

            case 'Fix missing avatars':
                return $this->fixMissingAvatars();
                break;
        }
    }

    protected function uploadDefaultAvatar()
    {
        if(!$this->confirm('Are you sure you want to upload the default avatar to the cloud storage disk?')) {
            return;
        }
        $disk = Storage::disk(config_cache('filesystems.cloud'));
        $disk->put('cache/avatars/default.jpg', Storage::get('public/avatars/default.jpg'));
        Avatar::whereMediaPath('public/avatars/default.jpg')->update(['cdn_url' => $disk->url('cache/avatars/default.jpg')]);
        $this->info('Successfully uploaded default avatar to cloud storage!');
        $this->info($disk->url('cache/avatars/default.jpg'));
    }

    protected function uploadAvatarsToCloud()
    {
        if(!config_cache('pixelfed.cloud_storage') || !config('instance.avatar.local_to_cloud')) {
            $this->error('Enable cloud storage and avatar cloud storage to perform this action');
            return;
        }
        $confirm = $this->confirm('Are you sure you want to move local avatars to cloud storage?');
        if(!$confirm) {
            $this->error('Aborted action');
            return;
        }

        $disk = Storage::disk(config_cache('filesystems.cloud'));

        if($disk->missing('cache/avatars/default.jpg')) {
            $disk->put('cache/avatars/default.jpg', Storage::get('public/avatars/default.jpg'));
        }

        Avatar::whereNull('is_remote')->chunk(5, function($avatars) use($disk) {
            foreach($avatars as $avatar) {
                if($avatar->media_path === 'public/avatars/default.jpg') {
                    $avatar->cdn_url = $disk->url('cache/avatars/default.jpg');
                    $avatar->save();
                } else {
                    if(!$avatar->media_path || !Str::of($avatar->media_path)->startsWith('public/avatars/')) {
                        continue;
                    }
                    $ext = pathinfo($avatar->media_path, PATHINFO_EXTENSION);
                    $newPath = 'cache/avatars/' . $avatar->profile_id . '/avatar_' . strtolower(Str::random(6)) . '.' . $ext;
                    $existing = Storage::disk('local')->get($avatar->media_path);
                    if(!$existing) {
                        continue;
                    }
                    $newMediaPath = $disk->put($newPath, $existing);
                    $avatar->media_path = $newPath;
                    $avatar->cdn_url = $disk->url($newPath);
                    $avatar->save();
                }

                Cache::forget('avatar:' . $avatar->profile_id);
                Cache::forget(AccountService::CACHE_KEY . $avatar->profile_id);
            }
        });
    }

    protected function refetchRemoteAvatars()
    {
        if(!$this->confirm('Are you sure you want to refetch all remote avatars? This could take a while.')) {
            return;
        }

        if(config_cache('pixelfed.cloud_storage') == false && config_cache('federation.avatars.store_local') == false) {
            $this->error('You have cloud storage disabled and local avatar storage disabled, we cannot refetch avatars.');
            return;
        }

        $count = Profile::has('avatar')
            ->with('avatar')
            ->whereNull('user_id')
            ->count();

        $this->info('Found ' . $count . ' remote avatars to re-fetch');
        $this->line(' ');
        $bar = $this->output->createProgressBar($count);

        Profile::has('avatar')
            ->with('avatar')
            ->whereNull('user_id')
            ->chunk(50, function($profiles) use($bar) {
            foreach($profiles as $profile) {
                $avatar = $profile->avatar;
                $avatar->last_fetched_at = null;
                $avatar->save();
                RemoteAvatarFetch::dispatch($profile)->onQueue('low');
                $bar->advance();
            }
        });
        $this->line(' ');
        $this->line(' ');
        $this->info('Finished dispatching avatar refetch jobs!');
        $this->line(' ');
        $this->info('This may take a few minutes to complete, you may need to run "php artisan cache:clear" after the jobs are processed.');
        $this->line(' ');
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

    protected function fixMissingAvatars()
    {
        if(!$this->confirm('Are you sure you want to fix missing avatars?')) {
            return;
        }

        $this->info('Found ' . $this->missing . ' accounts with missing profiles');

        Profile::where('created_at', '<', now()->subDays(1))
            ->doesntHave('avatar')
            ->chunk(50, function($profiles) {
                foreach($profiles as $profile) {
                    Avatar::updateOrCreate([
                        'profile_id' => $profile->id
                    ], [
                        'media_path' => 'public/avatars/default.jpg',
                        'is_remote' => $profile->domain == null && $profile->private_key == null
                    ]);
                    $this->incr('fixed');
                }
        });

        $this->line(' ');
        $this->line(' ');
        $this->info('Fixed ' . $this->fixed . ' accounts with a blank avatar');
    }
}
