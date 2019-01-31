<?php

namespace App\Jobs;

use App\Avatar;
use App\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportAvatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url;
    protected $profile;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($url, Profile $profile)
    {
        $this->url = $url;
        $this->profile = $profile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = $this->url;
        $profile = $this->profile;

        $basePath = $this->buildPath();
    }

    public function buildPath()
    {
        $baseDir = storage_path('app/public/avatars');
        if (!is_dir($baseDir)) {
            mkdir($baseDir);
        }

        $prefix = $this->profile->id;
        $padded = str_pad($prefix, 12, 0, STR_PAD_LEFT);
        $parts = str_split($padded, 3);
        foreach ($parts as $k => $part) {
            if ($k == 0) {
                $prefix = storage_path('app/public/avatars/'.$parts[0]);
                if (!is_dir($prefix)) {
                    mkdir($prefix);
                }
            }
            if ($k == 1) {
                $prefix = storage_path('app/public/avatars/'.$parts[0].'/'.$parts[1]);
                if (!is_dir($prefix)) {
                    mkdir($prefix);
                }
            }
            if ($k == 2) {
                $prefix = storage_path('app/public/avatars/'.$parts[0].'/'.$parts[1].'/'.$parts[2]);
                if (!is_dir($prefix)) {
                    mkdir($prefix);
                }
            }
            if ($k == 3) {
                $avatarpath = 'public/avatars/'.$parts[0].'/'.$parts[1].'/'.$parts[2].'/'.$parts[3];
                $prefix = storage_path('app/'.$avatarpath);
                if (!is_dir($prefix)) {
                    mkdir($prefix);
                }
            }
        }
        $dir = storage_path('app/'.$avatarpath);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $path = $avatarpath.'/avatar.svg';
        return storage_path('app/'.$path);
    }
}
