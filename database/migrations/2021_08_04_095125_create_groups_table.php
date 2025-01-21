<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->primary();
            $table->bigInteger('profile_id')->unsigned()->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->boolean('local')->default(true)->index();
            $table->string('remote_url')->nullable();
            $table->string('inbox_url')->nullable();
            $table->boolean('is_private')->default(false);
            $table->boolean('local_only')->default(false);
            $table->json('metadata')->nullable();
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
        Schema::dropIfExists('groups');
    }
}
