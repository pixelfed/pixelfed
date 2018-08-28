<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('status_id')->unsigned()->nullable();
            $table->bigInteger('profile_id')->unsigned()->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->string('media_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('cdn_url')->nullable();
            $table->string('optimized_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->tinyInteger('order')->unsigned()->default(1);
            $table->string('mime')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('orientation')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unique(['status_id', 'media_path']);
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
        Schema::dropIfExists('media');
    }
}
