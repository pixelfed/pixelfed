<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('status_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('status_id')->unsigned()->nullable()->index();
            $table->bigInteger('status_profile_id')->unsigned()->nullable()->index();
            $table->bigInteger('profile_id')->unsigned()->nullable()->index();
            $table->unique(['status_id', 'profile_id']);
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
        Schema::dropIfExists('status_views');
    }
}
