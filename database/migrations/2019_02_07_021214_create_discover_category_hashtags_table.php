<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiscoverCategoryHashtagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discover_category_hashtags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('discover_category_id')->unsigned()->index();
            $table->bigInteger('hashtag_id')->unsigned()->index();
            $table->unique(['discover_category_id', 'hashtag_id'], 'disc_hashtag_unique');
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
        Schema::dropIfExists('discover_category_hashtags');
    }
}
