<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRepliesCountToStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->unsignedInteger('reply_count')->nullable();
            $table->boolean('comments_disabled')->default(false);
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
            $table->dropColumn(['reply_count','comments_disabled']);
        });
    }
}
