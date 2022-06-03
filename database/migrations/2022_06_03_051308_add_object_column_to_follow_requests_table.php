<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddObjectColumnToFollowRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('follow_requests', function (Blueprint $table) {
            $table->json('activity')->nullable()->after('following_id');
            $table->timestamp('handled_at')->nullable()->after('is_local');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('follow_requests', function (Blueprint $table) {
            $table->dropColumn('activity');
            $table->dropColumn('handled_at');
        });
    }
}
