<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupInteractionsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('group_interactions', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->bigInteger('group_id')->unsigned()->index();
			$table->bigInteger('profile_id')->unsigned()->index();
			$table->string('type')->nullable()->index();
			$table->string('item_type')->nullable()->index();
			$table->string('item_id')->nullable()->index();
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
		Schema::dropIfExists('group_interactions');
	}
}
