<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStoriesTableFixExpiresAtColumn extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('stories', function (Blueprint $table) {
			$sm = Schema::getConnection()->getDoctrineSchemaManager();
			$doctrineTable = $sm->listTableDetails('stories');

			if($doctrineTable->hasIndex('stories_expires_at_index')) {
				$table->dropIndex('stories_expires_at_index');
			}
			$table->timestamp('expires_at')->default(null)->index()->nullable()->change();
			$table->boolean('can_reply')->default(true);
			$table->boolean('can_react')->default(true);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('stories', function (Blueprint $table) {
			$sm = Schema::getConnection()->getDoctrineSchemaManager();
			$doctrineTable = $sm->listTableDetails('stories');

			if($doctrineTable->hasIndex('stories_expires_at_index')) {
				$table->dropIndex('stories_expires_at_index');
			}
			$table->timestamp('expires_at')->default(null)->index()->nullable()->change();
			$table->dropColumn('can_reply');
			$table->dropColumn('can_react');
		});
	}
}
