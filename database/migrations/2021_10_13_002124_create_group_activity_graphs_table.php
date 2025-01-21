<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupActivityGraphsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_activity_graphs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('instance_id')->nullable()->index();
            $table->bigInteger('actor_id')->nullable()->index();
            $table->string('verb')->nullable()->index();
            $table->string('id_url')->nullable()->unique()->index();
            $table->json('payload')->nullable();
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
        Schema::dropIfExists('group_activity_graphs');
    }
}
