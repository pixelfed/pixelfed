<?php

namespace App\Console\Commands\FixBugs;

use App\Jobs\AvatarPipeline\CreateAvatar;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Models\DefaultDomainBlock;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserDomainBlock;
use App\Models\UserSetting;
use App\Util\ActivityPub\KeyPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\search;

class FixMissingUserProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-missing-user-profile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = search(
            label: 'Search for the affected username',
            options: fn (string $value) => $value !== ''
                ? User::doesntHave('profile')->whereNull('status')->whereLike('username', "%{$value}%")->pluck('username', 'id')->all()
                : []
        );

        if (! $id) {
            $this->error('Found none');
        }

        $user = User::find($id);

        if ($user->profile) {
            $this->error('Already has profile');

            return;
        }

        if (in_array($user->status, ['deleted', 'delete'])) {
            $this->error('User has deleted account');

            return;
        }

        if (Profile::whereUsername($user->username)->exists()) {
            $this->error('Already has profile');

            return;
        }

        if (empty($user->profile)) {
            $profile = DB::transaction(function () use ($user) {
                $profile = new Profile;
                $profile->user_id = $user->id;
                $profile->username = $user->username;
                $profile->name = $user->name;
                $keyPair = KeyPair::generate();

                $profile->private_key = $keyPair['private'];
                $profile->public_key = $keyPair['public'];
                $profile->save();
                $this->applyDefaultDomainBlocks($user);

                return $profile;
            });

            DB::transaction(function () use ($user, $profile) {
                $user = User::findOrFail($user->id);
                $user->profile_id = $profile->id;
                $user->save();

                CreateAvatar::dispatch($profile);
            });

            if ((bool) config_cache('account.autofollow') === true) {
                $names = config_cache('account.autofollow_usernames');
                $names = explode(',', $names);

                if (! $names || ! last($names)) {
                    return;
                }

                $profiles = Profile::whereIn('username', $names)->get();

                if ($profiles) {
                    foreach ($profiles as $p) {
                        $follower = new Follower;
                        $follower->profile_id = $profile->id;
                        $follower->following_id = $p->id;
                        $follower->save();

                        FollowPipeline::dispatch($follower);
                    }
                }
            }
        }

        if (empty($user->settings)) {
            DB::transaction(function () use ($user) {
                UserSetting::firstOrCreate([
                    'user_id' => $user->id,
                ]);
            });
        }
    }

    protected function applyDefaultDomainBlocks($user)
    {
        if ($user->profile_id == null) {
            return;
        }
        $defaultDomainBlocks = DefaultDomainBlock::pluck('domain')->toArray();

        if (! $defaultDomainBlocks || ! count($defaultDomainBlocks)) {
            return;
        }

        foreach ($defaultDomainBlocks as $domain) {
            UserDomainBlock::updateOrCreate([
                'profile_id' => $user->profile_id,
                'domain' => strtolower(trim($domain)),
            ]);
        }
    }
}
