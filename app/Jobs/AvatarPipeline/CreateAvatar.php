<?php

namespace App\Jobs\AvatarPipeline;

use App\Avatar;
use App\Profile;
use App\Util\Identicon\Preprocessor\HashPreprocessor;
use Bitverse\Identicon\Color\Color;
use Bitverse\Identicon\Generator\RingsGenerator;
use Bitverse\Identicon\Identicon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateAvatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = $this->profile;
        $username = $profile->username;

        $generator = new RingsGenerator();
        $generator->setBackgroundColor(Color::parseHex('#FFFFFF'));

        $identicon = new Identicon(new HashPreprocessor('sha256'), $generator);

        $hash = $username.str_random(12);
        $icon = $identicon->getIcon($hash);

        try {
            $baseDir = storage_path('app/public/avatars');
            if (!is_dir($baseDir)) {
                mkdir($baseDir);
            }

            $prefix = $profile->id;
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
            //$dir = storage_path('app/public/avatars/'.$prefix);
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            //$path = 'public/avatars/' . $prefix . '/avatar.svg';
            $path = $avatarpath.'/avatar.svg';
            $basePath = storage_path('app/'.$path);
            file_put_contents($basePath, $icon);
        } catch (Exception $e) {
        }

        $avatar = new Avatar();
        $avatar->profile_id = $profile->id;
        $avatar->media_path = $path;
        $avatar->thumb_path = $path;
        $avatar->change_count = 0;
        $avatar->last_processed_at = \Carbon\Carbon::now();
        $avatar->save();
    }
}
