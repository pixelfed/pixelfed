<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Avatar;
use App\User;
use Storage;
use App\Util\Lexer\PrettyNumber;

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
                Avatar::whereNotNull('is_remote')->count(),
                PrettyNumber::size(Avatar::whereNotNull('is_remote')->sum('size'))
            ],
            [
                'Cached (CDN)',
                Avatar::whereNotNull('cdn_url')->count(),
                PrettyNumber::size(Avatar::whereNotNull('cdn_url')->sum('size'))
            ],
            [
                'Uncached',
                Avatar::whereNull('is_remote')->whereNull('cdn_url')->count(),
                PrettyNumber::size(Avatar::whereNull('is_remote')->whereNull('cdn_url')->sum('size'))
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

        if(config_cache('instance.avatar.local_to_cloud')) {
            $this->info('✅ - Store avatars on cloud filesystem');
            $this->line(' ');
        }

        if(config_cache('pixelfed.cloud_storage') && config_cache('instance.avatar.local_to_cloud')) {
            $disk = Storage::disk(config_cache('filesystems.cloud'));
            $exists = $disk->exists('cache/avatars/default.jpg');
            $state = $exists ? '✅' : '❌';
            $msg = $state . ' - Cloud default avatar exists';
            $this->info($msg);
        }

        $choice = $this->choice(
            'Select action:',
            [
                'Upload default avatar to cloud',
                'Move local avatars to cloud',
                'Move cloud avatars to local'
            ],
            0
        );

        return $this->handleChoice($choice);
    }

    protected function handleChoice($id)
    {
        switch ($id) {
            case 'Upload default avatar to cloud':
                return $this->uploadDefaultAvatar();
                break;
        }
    }

    protected function uploadDefaultAvatar()
    {
        $disk = Storage::disk(config_cache('filesystems.cloud'));
        $disk->put('cache/avatars/default.jpg', Storage::get('public/avatars/default.jpg'));
        Avatar::whereMediaPath('public/avatars/default.jpg')->update(['cdn_url' => $disk->url('cache/avatars/default.jpg')]);
        $this->info('Successfully uploaded default avatar to cloud storage!');
        $this->info($disk->url('cache/avatars/default.jpg'));
    }
}
