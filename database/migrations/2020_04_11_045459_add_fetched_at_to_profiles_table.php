<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFetchedAtToProfilesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('profiles', function (Blueprint $table) {
			$table->timestamp('last_fetched_at')->nullable();
			$table->unsignedInteger('status_count')->default(0)->nullable();
			$table->unsignedInteger('followers_count')->default(0)->nullable();
			$table->unsignedInteger('following_count')->default(0)->nullable();
			$table->string('webfinger')->unique()->nullable()->index();
			$table->string('avatar_url')->nullable();
			$table->dropColumn('keybase_proof');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('profiles', function (Blueprint $table) {
			$table->dropColumn(['last_fetched_at','status_count','followers_count','following_count','webfinger','avatar_url']);
			$table->text('keybase_proof')->nullable()->after('post_layout');
		});
	}
}
