<?php

namespace App\Console\Commands\User;

use App\Models\Avatar;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Signature('user:avatar-delete {username} {--force : Delete without confirmation prompts}')]
#[Description('Delete user avatar and reset to default')]
class UserAvatarDelete extends Command implements PromptsForMissingInput
{
    /**
     * @var array<int, string>
     */
    protected array $defaultPaths = [
        'public/avatars/default.jpg',
        'public/avatars/default.png',
    ];

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
    public function handle(): int
    {
        $user = User::whereUsername($this->argument('username'))->first();

        if (! $user) {
            $this->error('Could not find any user with that username');

            return Command::FAILURE;
        }

        if (! $user->profile_id) {
            $this->error('Could not find the profile with that username');

            return Command::FAILURE;
        }

        $pid = $user->profile_id;

        $avatarModel = Avatar::whereProfileId($pid)->first();

        if (! $avatarModel) {
            $this->error('No avatar model found');
            $this->forgetAvatarCaches($pid);

            return Command::FAILURE;
        }

        if ($this->isDefaultAvatar($avatarModel->media_path)) {
            $this->info('Default avatar already used, aborting...');
            $this->forgetAvatarCaches($pid);

            return Command::SUCCESS;
        }

        if (! $this->deleteStoredAvatar($avatarModel->media_path)) {
            $this->info('Aborted, no changes were made.');

            return Command::SUCCESS;
        }

        $avatarModel->media_path = 'public/avatars/default.jpg';
        $avatarModel->cdn_url = null;
        $avatarModel->change_count = $avatarModel->change_count + 1;
        $avatarModel->save();

        $this->forgetAvatarCaches($pid);

        $this->info('Successfully deleted user avatar!');

        return Command::SUCCESS;
    }

    protected function isDefaultAvatar(?string $path): bool
    {
        return in_array($path, $this->defaultPaths, true);
    }

    protected function forgetAvatarCaches($pid): void
    {
        Cache::forget('avatar:'.$pid);
        AccountService::del($pid);
    }

    protected function deleteStoredAvatar(string $path): bool
    {
        if ((bool) config_cache('pixelfed.cloud_storage')) {
            $cloudDisk = Storage::disk(config('filesystems.cloud'));

            if ($cloudDisk->exists($path)) {
                if (! $this->option('force') && ! $this->confirm("Found a cloud avatar at {$path}! Are you sure you want to delete this?")) {
                    return false;
                }

                $cloudDisk->delete($path);
                $this->info('Deleting cloud avatar copy');
            }
        }

        if (Storage::disk('local')->exists($path)) {
            if (! $this->option('force') && ! $this->confirm("Found a local avatar at {$path}! Are you sure you want to delete this?")) {
                return false;
            }

            Storage::disk('local')->delete($path);
            $this->info('Deleting local avatar copy');
        }

        return true;
    }
}
