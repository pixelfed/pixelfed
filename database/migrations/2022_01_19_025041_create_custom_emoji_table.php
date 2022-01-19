<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomEmojiTable extends Migration
{
	/**
	* Run the migrations.
	*
	* @return void
	*/
	public function up()
	{
		Schema::create('custom_emoji', function (Blueprint $table) {
			$table->id();
			$table->string('shortcode')->index();
			$table->string('media_path')->nullable();
			$table->string('domain')->nullable()->index();
			$table->boolean('disabled')->default(false)->index();
			$table->string('uri')->nullable();
			$table->string('image_remote_url')->nullable();
			$table->unsignedInteger('category_id')->nullable();
			$table->unique(['shortcode', 'domain']);
			$table->timestamps();
		});

		Schema::create('custom_emoji_categories', function (Blueprint $table) {
			$table->id();
			$table->string('name')->unique()->index();
			$table->boolean('disabled')->default(false)->index();
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
		Schema::dropIfExists('custom_emoji');
		Schema::dropIfExists('custom_emoji_categories');
	}
}
