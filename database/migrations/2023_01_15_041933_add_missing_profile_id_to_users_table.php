<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Profile;
use App\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        User::whereNull('profile_id')
        ->chunk(20, function($users) {
            foreach($users as $user) {
                $profile = Profile::whereUsername($user->username)->first();
                if($profile) {
                    $user->profile_id = $profile->id;
                    $user->save();
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

    }
};
