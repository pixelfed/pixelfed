<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateImportDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('import_datas', function (Blueprint $table) {
            $table->bigInteger('job_id')->unsigned()->nullable()->after('profile_id');
            $table->string('original_name')->nullable()->after('stage');
            $table->boolean('import_accepted')->default(false)->nullable()->after('original_name');
            $table->unique(['job_id', 'original_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
