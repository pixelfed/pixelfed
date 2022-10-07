<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReblogOfIdIndexToStatusesTable extends Migration
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
            if(array_key_exists('statuses_in_reply_or_reblog_index', $sc)) {
                $table->dropIndex('statuses_in_reply_or_reblog_index');
            }

            $table->index('in_reply_to_id');
            $table->index('reblog_of_id');
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
