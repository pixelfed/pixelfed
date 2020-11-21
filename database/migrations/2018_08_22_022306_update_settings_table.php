<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('show_profile_followers')->default(true);
            $table->boolean('show_profile_follower_count')->default(true);
            $table->boolean('show_profile_following')->default(true);
            $table->boolean('show_profile_following_count')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('show_profile_followers');
            $table->dropColumn('show_profile_follower_count');
            $table->dropColumn('show_profile_following');
            $table->dropColumn('show_profile_following_count');
        });
    }
}
