<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDirectMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('direct_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('to_id')->unsigned()->index();
            $table->bigInteger('from_id')->unsigned()->index();
            $table->string('from_profile_ids')->nullable();
            $table->boolean('group_message')->default(false);
            $table->bigInteger('status_id')->unsigned()->integer();
            $table->unique(['to_id', 'from_id', 'status_id']);
            $table->timestamp('read_at')->nullable();
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
        Schema::dropIfExists('direct_messages');
    }
}
