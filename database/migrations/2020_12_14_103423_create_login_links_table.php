<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_links', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('secret')->index();
            $table->unsignedInteger('user_id')->index();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamp('resent_at')->nullable()->index();
            $table->timestamp('used_at')->nullable()->index();
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
        Schema::dropIfExists('login_links');
    }
}
