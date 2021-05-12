<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPronounsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_pronouns', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable()->unique()->index();
            $table->bigInteger('profile_id')->unique()->index();
            $table->json('pronouns')->nullable();
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
        Schema::dropIfExists('user_pronouns');
    }
}
