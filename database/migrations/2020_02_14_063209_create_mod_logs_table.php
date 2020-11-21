<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mod_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned()->index();
            $table->string('user_username')->nullable();
            $table->bigInteger('object_uid')->nullable()->unsigned()->index();
            $table->bigInteger('object_id')->nullable()->unsigned()->index();
            $table->string('object_type')->nullable()->index();
            $table->string('action')->nullable();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->string('access_level')->default('admin')->nullable();
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
        Schema::dropIfExists('mod_logs');
    }
}
