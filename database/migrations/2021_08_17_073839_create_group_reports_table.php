<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_reports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('group_id')->unsigned()->index();
			$table->bigInteger('profile_id')->unsigned()->index();
			$table->string('type')->nullable()->index();
			$table->string('item_type')->nullable()->index();
			$table->string('item_id')->nullable()->index();
			$table->json('metadata')->nullable();
			$table->boolean('open')->default(true)->index();
			$table->unique(['group_id', 'profile_id', 'item_type', 'item_id']);
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
        Schema::dropIfExists('group_reports');
    }
}
