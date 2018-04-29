<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uri')->nullable();          
            $table->string('caption')->nullable();
            $table->unsignedInteger('profile_id')->nullable();
            $table->bigInteger('in_reply_to_id')->unsigned()->nullable();
            $table->bigInteger('reblog_of_id')->unsigned()->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_nsfw')->default(false);
            $table->enum('visibility', ['public', 'unlisted', 'private', 'direct'])->default('public');
            $table->boolean('reply')->default(false);
            $table->bigInteger('likes_count')->unsigned()->default(0);
            $table->bigInteger('reblogs_count')->unsigned()->default(0);
            $table->string('language')->nullable();
            $table->bigInteger('conversation_id')->unsigned()->nullable();
            $table->boolean('local')->default(true);
            $table->bigInteger('application_id')
            $table->bigInteger('in_reply_to_profile_id')->unsigned()->nullable();
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
        Schema::dropIfExists('statuses');
    }
}
