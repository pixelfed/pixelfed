<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Profile;

class GenerateMissingProfileWebfinger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Profile::whereNotNull('domain')
            ->whereNull('webfinger')
            ->chunk(200, function($profiles) {
                foreach($profiles as $profile) {
                    if(substr($profile->username, 0, 1) === "@") {
                        $profile->webfinger = $profile->username;
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
