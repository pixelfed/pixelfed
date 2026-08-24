<?php

namespace App\Console\Commands;

use App\Avatar;
use App\Services\AccountService;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class UserAvatarDelete extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:avatar-delete {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user avatar';

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'username' => 'Which username should we delete the avatar for?',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::whereUsername($this->argument('username'))->first();

        if (! $user) {
            $this->error('Could not find any user with that username');
            exit;
        }

        if (! $user->profile_id) {
            $this->error('Could not find the profile with that username');
            exit;
        }

        $pid = $user->profile_id;

        $avatarModel = Avatar::where('profile_id', $pid)->first();

        if (! $avatarModel) {
            $this->error('No avatar model found');
            Cache::forget('avatar:'.$pid);
            exit;
        }

        $defaultPaths = ['public/avatars/default.jpg', 'public/avatars/default.png'];
        $mediaPath = $avatarModel->media_path;

        if (in_array($mediaPath, $defaultPaths)) {
            $this->info('Default avatar already used, aborting...');
            Cache::forget('avatar:'.$pid);
            exit;
        }

        if (Storage::disk(config('filesystems.cloud'))->exists($mediaPath)) {
            if ($this->confirm('Found a S3 avatar at '.$mediaPath.'! Are you sure you want to delete this?')) {
                Storage::disk(config('filesystems.cloud'))->delete($mediaPath);
                $this->info('Deleting S3 copy');
            } else {
                exit;
            }
        }

        if (Storage::disk('local')->exists($mediaPath)) {
            if ($this->confirm('Found a local avatar at '.$mediaPath.'! Are you sure you want to delete this?')) {
                Storage::disk('local')->delete($mediaPath);
                $this->info('Deleting local copy');
            } else {
                exit;
            }
        }

        $avatarModel->media_path = 'public/avatars/default.jpg';
        $avatarModel->cdn_url = null;
        $avatarModel->save();
        Cache::forget('avatar:'.$pid);
        AccountService::del($pid);

        $this->info('Successfully deleted user avatar!');
    }
}
