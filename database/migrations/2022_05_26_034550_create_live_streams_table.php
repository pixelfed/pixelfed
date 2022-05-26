<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiveStreamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('profile_id')->unsigned()->index();
            $table->string('stream_id')->nullable()->unique()->index();
            $table->string('stream_key')->nullable();
            $table->string('visibility')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('live_chat')->default(true);
            $table->json('mod_ids')->nullable();
            $table->boolean('discoverable')->nullable();
            $table->timestamp('live_at')->nullable();
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
        Schema::dropIfExists('live_streams');
    }
}
