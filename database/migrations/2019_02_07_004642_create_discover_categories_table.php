<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiscoverCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discover_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('slug')->unique()->index();
            $table->boolean('active')->default(false)->index();
            $table->tinyInteger('order')->unsigned()->default(5);
            $table->bigInteger('media_id')->unsigned()->unique()->nullable();
            $table->boolean('no_nsfw')->default(true);
            $table->boolean('local_only')->default(true);
            $table->boolean('public_only')->default(true);
            $table->boolean('photos_only')->default(true);
            $table->timestamp('active_until')->nullable();
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
        Schema::dropIfExists('discover_categories');
    }
}
