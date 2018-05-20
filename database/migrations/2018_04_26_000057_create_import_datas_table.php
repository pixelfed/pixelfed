<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_datas', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('profile_id')->unsigned();
            $table->string('service')->default('instagram');
            $table->string('path')->nullable();
            $table->tinyInteger('stage')->unsigned()->default(1);
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
        Schema::dropIfExists('import_datas');
    }
}
