<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned()->index();
            $table->string('ip')->index();
            $table->string('user_agent')->index();
            $table->string('fingerprint')->nullable();
            $table->string('name')->nullable();
            $table->boolean('trusted')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->unique(['user_id', 'ip', 'user_agent', 'fingerprint'], 'user_ip_agent_index');
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
        Schema::dropIfExists('user_devices');
    }
}
