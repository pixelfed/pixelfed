<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStoriesTable extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('stories');
        Schema::dropIfExists('story_items');
        Schema::dropIfExists('story_reactions');
        Schema::dropIfExists('story_views');

        Schema::create('stories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('profile_id')->unsigned()->index();
            $table->string('type')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('mime')->nullable();
            $table->smallInteger('duration')->unsigned();
            $table->string('path')->nullable();
            $table->string('cdn_url')->nullable();
            $table->boolean('public')->default(false)->index();
            $table->boolean('local')->default(false)->index();
            $table->unsignedInteger('view_count')->nullable();
            $table->unsignedInteger('comment_count')->nullable();
            $table->json('story')->nullable();
            $table->unique(['profile_id', 'path']);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('story_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('story_id')->unsigned()->index();
            $table->bigInteger('profile_id')->unsigned()->index();
            $table->unique(['profile_id', 'story_id']);
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
        Schema::dropIfExists('stories');
        Schema::dropIfExists('story_views');
    }
}
