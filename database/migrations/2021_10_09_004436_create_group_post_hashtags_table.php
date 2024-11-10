<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupPostHashtagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_post_hashtags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('hashtag_id')->unsigned()->index();
            $table->bigInteger('group_id')->unsigned()->index();
            $table->bigInteger('profile_id')->unsigned();
            $table->bigInteger('status_id')->unsigned()->nullable();
            $table->string('status_visibility')->nullable();
            $table->boolean('nsfw')->default(false);
            $table->unique(['hashtag_id', 'group_id', 'profile_id', 'status_id'], 'group_post_hashtags_gda_unique');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
            $table->foreign('hashtag_id')->references('id')->on('group_hashtags')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('group_posts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_post_hashtags');
    }
}
