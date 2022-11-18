<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;
use App\Profile;
use App\User;
use App\Instance;
use App\Util\ActivityPub\Helpers;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SendUpdateActor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ap:update-actors {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Update Actor activities to known remote servers to force updates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $totalUserCount = Profile::whereNotNull('user_id')->count();
        $totalInstanceCount = Instance::count();
        $this->info('Found ' . $totalUserCount . ' local accounts and ' . $totalInstanceCount . ' remote instances');

        $task = $this->choice(
            'What do you want to do?',
            [
                'View top instances',
                'Send updates to an instance'
            ],
            0
        );

        if($task === 'View top instances') {
            $this->table(
                ['domain', 'user_count', 'last_synced'],
                Instance::orderByDesc('user_count')->take(20)->get(['domain', 'user_count', 'actors_last_synced_at'])->toArray()
            );
            return Command::SUCCESS;
        } else {
            $domain = $this->anticipate('Enter the instance domain', function ($input) {
                return Instance::where('domain', 'like', '%' . $input . '%')->pluck('domain')->toArray();
            });
            if(!$this->confirm('Are you sure you want to send actor updates to ' . $domain . '?')) {
                return;
            }
            if($cur = Instance::whereDomain($domain)->whereNotNull('actors_last_synced_at')->first()) {
                if(!$this->option('force')) {
                    $this->error('ERROR: Cannot re-sync this instance, it was already synced on ' . $cur->actors_last_synced_at);
                    return;
                }
            }
            $this->touchStorageCache($domain);
            $this->line(' ');
            $this->error('Keep this window open during this process or it will not complete!');
            $sharedInbox = Profile::whereDomain($domain)->whereNotNull('sharedInbox')->first();
            if(!$sharedInbox) {
                $this->error('ERROR: Cannot find the sharedInbox of ' . $domain);
                return;
            }
            $url = $sharedInbox->sharedInbox;
            $this->line(' ');
            $this->info('Found sharedInbox: ' . $url);
            $bar = $this->output->createProgressBar($totalUserCount);
            $bar->start();

            $startCache = $this->getStorageCache($domain);
            User::whereNull('status')->when($startCache, function($query, $startCache) use($bar) {
                $bar->advance($startCache);
                return $query->where('id', '>', $startCache);
            })->chunk(50, function($users) use($bar, $url, $domain) {
                foreach($users as $user) {
                    $this->updateStorageCache($domain, $user->id);
                    $profile = Profile::find($user->profile_id);
                    if(!$profile) {
                        continue;
                    }
                    $body = $this->updateObject($profile);
                    try {
                        Helpers::sendSignedObject($profile, $url, $body);
                    } catch (HttpException $e) {
                        continue;
                    }
                    $bar->advance();
                }
            });
            $bar->finish();
            $this->line(' ');
            $instance = Instance::whereDomain($domain)->firstOrFail();
            $instance->actors_last_synced_at = now();
            $instance->save();
            $this->info('Finished!');
            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }

    protected function updateObject($profile)
    {
        return [
            '@context' => [
                'https://w3id.org/security/v1',
                'https://www.w3.org/ns/activitystreams',
                [
                    'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
                ],
            ],
            'id' => $profile->permalink('#updates/' . time()),
            'actor' => $profile->permalink(),
            'type' => 'Update',
            'object' => $this->actorObject($profile)
        ];
    }

    protected function touchStorageCache($domain)
    {
        $path = 'actor-update-cache/' . $domain;
        if(!Storage::exists($path)) {
            Storage::put($path, "");
        }
    }

    protected function getStorageCache($domain)
    {
        $path = 'actor-update-cache/' . $domain;
        return Storage::get($path);
    }

    protected function updateStorageCache($domain, $value)
    {
        $path = 'actor-update-cache/' . $domain;
        Storage::put($path, $value);
    }

    protected function actorObject($profile)
    {
        $permalink = $profile->permalink();
        return [
            'id'                        => $permalink,
            'type'                      => 'Person',
            'following'                 => $permalink . '/following',
            'followers'                 => $permalink . '/followers',
            'inbox'                     => $permalink . '/inbox',
            'outbox'                    => $permalink . '/outbox',
            'preferredUsername'         => $profile->username,
            'name'                      => $profile->name,
            'summary'                   => $profile->bio,
            'url'                       => $profile->url(),
            'manuallyApprovesFollowers' => (bool) $profile->is_private,
            'publicKey' => [
                'id'           => $permalink . '#main-key',
                'owner'        => $permalink,
                'publicKeyPem' => $profile->public_key,
            ],
            'icon' => [
                'type'      => 'Image',
                'mediaType' => 'image/jpeg',
                'url'       => $profile->avatarUrl(),
            ],
            'endpoints' => [
                'sharedInbox' => config('app.url') . '/f/inbox'
            ]
        ];
    }
}
