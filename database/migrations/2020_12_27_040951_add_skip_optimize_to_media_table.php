<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkipOptimizeToMediaTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('media', function (Blueprint $table) {
			$table->boolean('skip_optimize')->nullable()->index();
			$table->timestamp('replicated_at')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('media', function (Blueprint $table) {
			$table->dropColumn(['skip_optimize','replicated_at']);
		});
	}
}
