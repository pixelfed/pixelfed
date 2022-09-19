<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\UserSetting;

class FixDoubleJsonEncodedSettingsInUsersettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        UserSetting::whereNotNull('compose_settings')
        ->chunk(50, function($settings) {
            foreach($settings as $userSetting) {
                if(is_array($userSetting->compose_settings)) {
                    continue;
                }
                $userSetting->compose_settings = json_decode($userSetting->compose_settings);
                $userSetting->save();
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
}
