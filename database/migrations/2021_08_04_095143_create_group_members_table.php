<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupMembersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('group_members', function (Blueprint $table) {
			$table->id();
			$table->bigInteger('group_id')->unsigned()->index();
			$table->bigInteger('profile_id')->unsigned()->index();
			$table->string('role')->default('member')->index();
			$table->boolean('local_group')->default(false)->index();
			$table->boolean('local_profile')->default(false)->index();
			$table->boolean('join_request')->default(false)->index();
			$table->timestamp('approved_at')->nullable();
			$table->timestamp('rejected_at')->nullable();
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
		Schema::dropIfExists('group_members');
	}
}
