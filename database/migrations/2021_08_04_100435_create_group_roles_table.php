<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupRolesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('group_roles', function (Blueprint $table) {
			$table->id();
			$table->bigInteger('group_id')->unsigned()->index();
			$table->string('name');
			$table->string('slug')->nullable();
			$table->text('abilities')->nullable();
			$table->unique(['group_id', 'slug']);
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
		Schema::dropIfExists('group_roles');
	}
}
