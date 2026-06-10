<?php

namespace App\Console\Commands;

use App\Avatar;
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
    protected $signature = 'user:avatar-delete {username} {--force : Delete without confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user avatar';

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

        $avatarModel = Avatar::where('profile_id', $pid)->first();

        if (! $avatarModel) {
            $this->error('No avatar model found');
            $this->forgetAvatarCaches($pid, $user->id);

            return Command::FAILURE;
        }

        if ($this->isDefaultAvatar($avatarModel->media_path)) {
            $this->info('Default avatar already used, aborting...');
            $this->forgetAvatarCaches($pid, $user->id);

            return Command::SUCCESS;
        }

        if (! $this->deleteStoredAvatar($avatarModel->media_path, 'avatar')) {
            return Command::FAILURE;
        }

        $avatarModel->media_path = 'public/avatars/default.jpg';
        $avatarModel->cdn_url = null;
        $avatarModel->change_count = $avatarModel->change_count + 1;
        $avatarModel->save();

        $this->forgetAvatarCaches($pid, $user->id);

        $this->info('Successfully deleted user avatar!');

        return Command::SUCCESS;
    }

    protected function isDefaultAvatar(?string $path): bool
    {
        return in_array($path, $this->defaultPaths, true);
    }

    protected function forgetAvatarCaches(int $profileId, int $userId): void
    {
        Cache::forget('avatar:'.$profileId);
        Cache::forget("avatar:{$profileId}");
        Cache::forget('user:account:id:'.$userId);
    }

    protected function deleteStoredAvatar(string $path, string $label): bool
    {
        if ((bool) config_cache('pixelfed.cloud_storage')) {
            $cloudDisk = Storage::disk(config('filesystems.cloud'));

            if ($cloudDisk->exists($path)) {
                if (! $this->option('force') && ! $this->confirm("Found a cloud {$label} at {$path}! Are you sure you want to delete this?")) {
                    return false;
                }

                $cloudDisk->delete($path);
                $this->info("Deleting cloud {$label} copy");
            }
        }

        if (Storage::disk('local')->exists($path)) {
            if (! $this->option('force') && ! $this->confirm("Found a local {$label} at {$path}! Are you sure you want to delete this?")) {
                return false;
            }

            Storage::disk('local')->delete($path);
            $this->info("Deleting local {$label} copy");
        } elseif (is_file(storage_path('app/'.$path))) {
            if (! $this->option('force') && ! $this->confirm("Found a local {$label} at {$path}! Are you sure you want to delete this?")) {
                return false;
            }

            @unlink(storage_path('app/'.$path));
            $this->info("Deleting local {$label} copy");
        }

        return true;
    }
}
