<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Stories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('story_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('story_id')->unsigned()->index();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->tinyInteger('duration')->unsigned();
            $table->string('filter')->nullable();
            $table->string('link_url')->nullable()->index();
            $table->string('link_text')->nullable();
            $table->tinyInteger('order')->unsigned()->nullable();
            $table->string('type')->default('photo');
            $table->json('layers')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('story_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('story_id')->unsigned()->index();
            $table->bigInteger('profile_id')->unsigned()->index();
            $table->unique(['story_id', 'profile_id']);
            $table->timestamps();
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->string('title')->nullable()->after('profile_id');
            $table->boolean('preview_photo')->default(false)->after('title');
            $table->boolean('local_only')->default(false)->after('preview_photo');
            $table->boolean('is_live')->default(false)->after('local_only');
            $table->string('broadcast_url')->nullable()->after('is_live');
            $table->string('broadcast_key')->nullable()->after('broadcast_url');
        });

        Schema::table('story_reactions', function (Blueprint $table) {
            $table->bigInteger('story_id')->unsigned()->index()->after('profile_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('story_items');
        Schema::dropIfExists('story_views');
        Schema::dropIfExists('story_reactions');
        Schema::dropIfExists('stories');
    }
}
