<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->unsignedInteger('status_id')->nullable();
            $table->unsignedInteger('profile_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('media_path');
            $table->string('cdn_url')->nullable();
            $table->tinyInteger('order')->unsigned()->default(1);
            $table->string('mime')->nullable();
            $table->unsignedInteger('size')->nullable();
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
