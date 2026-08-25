<?php

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Profile;
use Illuminate\Database\Migrations\Migration;

class FixWebfingerProfileDuplicateAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Profile::count() === 0) {
            return;
        }

        Profile::whereNotNull('domain')
            ->where('username', 'not like', '@%')
            ->chunk(200, function ($profiles) {
                foreach ($profiles as $profile) {
                    $exists = Profile::whereUsername("@{$profile->username}@{$profile->domain}")->first();
                    if ($exists) {
                        $exists->username = null;
                        $exists->domain = null;
                        $exists->webfinger = null;
                        $exists->save();
                        DeleteRemoteProfilePipeline::dispatch($exists);

                        $profile->username = "@{$profile->username}@{$profile->domain}";
                        if (! $profile->webfinger) {
                            $profile->webfinger = "@{$profile->username}@{$profile->domain}";
                        }
                        $profile->save();
                    } else {
                        $profile->username = "@{$profile->username}@{$profile->domain}";
                        if (! $profile->webfinger) {
                            $profile->webfinger = "@{$profile->username}@{$profile->domain}";
                        }
                        $profile->save();
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
