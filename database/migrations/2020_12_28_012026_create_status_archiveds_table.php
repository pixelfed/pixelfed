<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusArchivedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('status_archiveds', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('status_id')->unsigned()->index();
            $table->bigInteger('profile_id')->unsigned()->index();
            $table->string('original_scope')->nullable();
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
        Schema::dropIfExists('status_archiveds');
    }
}
