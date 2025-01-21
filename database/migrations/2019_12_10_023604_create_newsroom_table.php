<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsroomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('newsroom', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->string('header_photo_url')->nullable();
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->unique()->index();
            $table->string('category')->default('update');
            $table->text('summary')->nullable();
            $table->text('body')->nullable();
            $table->text('body_rendered')->nullable();
            $table->string('link')->nullable();
            $table->boolean('force_modal')->default(false);
            $table->boolean('show_timeline')->default(false);
            $table->boolean('show_link')->default(false);
            $table->boolean('auth_only')->default(true);
            $table->timestamp('published_at')->nullable();
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
        Schema::dropIfExists('newsroom');
    }
}
