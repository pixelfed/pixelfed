<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupLimitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_limits', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('group_id')->unsigned()->index();
			$table->bigInteger('profile_id')->unsigned()->index();
			$table->json('limits')->nullable();
			$table->json('metadata')->nullable();
			$table->unique(['group_id', 'profile_id']);
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
        Schema::dropIfExists('group_limits');
    }
}
