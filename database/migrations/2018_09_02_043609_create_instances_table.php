<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('domain')->unique()->index();
            $table->string('url')->nullable();
            $table->string('name')->nullable();
            $table->string('admin_url')->nullable();
            $table->string('limit_reason')->nullable();
            $table->boolean('unlisted')->default(false);
            $table->boolean('auto_cw')->default(false);
            $table->boolean('banned')->default(false);
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
        Schema::dropIfExists('instances');
    }
}
