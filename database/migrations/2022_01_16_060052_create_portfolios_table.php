<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortfoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable()->unique()->index();
            $table->bigInteger('profile_id')->unsigned()->unique()->index();
            $table->boolean('active')->nullable()->index();
            $table->boolean('show_captions')->default(true)->nullable();
            $table->boolean('show_license')->default(true)->nullable();
            $table->boolean('show_location')->default(true)->nullable();
            $table->boolean('show_timestamp')->default(true)->nullable();
            $table->boolean('show_link')->default(true)->nullable();
            $table->string('profile_source')->default('recent')->nullable();
            $table->boolean('show_avatar')->default(true)->nullable();
            $table->boolean('show_bio')->default(true)->nullable();
            $table->string('profile_layout')->default('grid')->nullable();
            $table->string('profile_container')->default('fixed')->nullable();
            $table->json('metadata')->nullable();
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
        Schema::dropIfExists('portfolios');
    }
}
