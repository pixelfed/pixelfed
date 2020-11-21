<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('profile_id')->unsigned();
            $table->string('service')->default('instagram');
            $table->string('uuid')->nullable();
            $table->string('storage_path')->nullable();
            $table->tinyInteger('stage')->unsigned()->default(0);
            $table->text('media_json')->nullable();
            $table->timestamp('completed_at')->nullable();
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
        Schema::dropIfExists('import_jobs');
    }
}
