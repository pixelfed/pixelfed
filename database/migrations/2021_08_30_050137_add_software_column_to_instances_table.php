<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Jobs\InstancePipeline\InstanceCrawlPipeline;

class AddSoftwareColumnToInstancesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('instances', function (Blueprint $table) {
			$table->string('software')->nullable()->index();
			$table->unsignedInteger('user_count')->nullable();
			$table->unsignedInteger('status_count')->nullable();
			$table->timestamp('last_crawled_at')->nullable();
		});

		$this->runPostMigration();
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('instances', function (Blueprint $table) {
			$table->dropColumn('software');
			$table->dropColumn('user_count');
			$table->dropColumn('status_count');
			$table->dropColumn('last_crawled_at');
		});
	}

	protected function runPostMigration()
	{
		InstanceCrawlPipeline::dispatch();
	}
}
