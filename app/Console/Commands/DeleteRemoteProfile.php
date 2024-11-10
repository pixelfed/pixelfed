<?php

namespace App\Console\Commands;

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Profile;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;

class DeleteRemoteProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-remote-profile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete remote profile';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = search(
            'Search for the account',
            fn (string $value) => strlen($value) > 2
                ? Profile::whereNotNull('domain')->where('username', 'like', $value.'%')->pluck('username', 'id')->all()
                : []
        );
        $profile = Profile::whereNotNull('domain')->find($id);

        if (! $profile) {
            $this->error('Could not find profile.');
            exit;
        }

        $confirmed = confirm('Are you sure you want to delete '.$profile->username.'\'s account? This action cannot be reversed.');
        DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('adelete');
        $this->info('Dispatched delete job, it may take a few minutes...');
        exit;
    }
}
