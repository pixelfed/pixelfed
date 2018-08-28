<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned()->unique();
            $table->string('role')->default('user');
            $table->boolean('crawlable')->default(true);
            $table->boolean('show_guests')->default(true);
            $table->boolean('show_discover')->default(true);
            $table->boolean('public_dm')->default(false);
            $table->boolean('hide_cw_search')->default(true);
            $table->boolean('hide_blocked_search')->default(true);
            $table->boolean('always_show_cw')->default(false);
            $table->boolean('compose_media_descriptions')->default(false);
            $table->boolean('reduce_motion')->default(false);
            $table->boolean('optimize_screen_reader')->default(false);
            $table->boolean('high_contrast_mode')->default(false);
            $table->boolean('video_autoplay')->default(false);
            $table->boolean('send_email_new_follower')->default(false);
            $table->boolean('send_email_new_follower_request')->default(true);
            $table->boolean('send_email_on_share')->default(false);
            $table->boolean('send_email_on_like')->default(false);
            $table->boolean('send_email_on_mention')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_settings');
    }
}
