<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveOldCompoundIndexFromStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statuses', function (Blueprint $table) {
            $sc = Schema::getConnection()->getDoctrineSchemaManager();
            if(array_key_exists('statuses_in_reply_to_id_reblog_of_id_index', $sc->listTableIndexes('statuses'))) {
                $table->dropIndex('statuses_in_reply_to_id_reblog_of_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('statuses', function (Blueprint $table) {
            //
        });
    }
}
