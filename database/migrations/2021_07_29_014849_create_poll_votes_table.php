<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('story_id')->unsigned()->nullable()->index();
            $table->bigInteger('status_id')->unsigned()->nullable()->index();
            $table->bigInteger('profile_id')->unsigned()->index();
            $table->bigInteger('poll_id')->unsigned()->index();
            $table->unsignedInteger('choice')->default(0)->index();
            $table->string('uri')->nullable();
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
        Schema::dropIfExists('poll_votes');
    }
}
