<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupPostsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('group_posts', function (Blueprint $table) {
			$table->bigInteger('id')->unsigned()->primary();
			$table->bigInteger('group_id')->unsigned()->index();
			$table->bigInteger('profile_id')->unsigned()->nullable()->index();
			$table->string('type')->nullable()->index();
			$table->bigInteger('status_id')->unsigned()->unique();
			$table->string('remote_url')->unique()->nullable()->index();
			$table->bigInteger('reply_child_id')->unsigned()->nullable();
			$table->bigInteger('in_reply_to_id')->unsigned()->nullable();
			$table->bigInteger('reblog_of_id')->unsigned()->nullable();
			$table->unsignedInteger('reply_count')->nullable();
			$table->string('status')->nullable()->index();
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
		Schema::dropIfExists('group_posts');
	}
}
