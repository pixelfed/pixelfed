<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusProfileIdToLikesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->bigInteger('status_profile_id')->nullable()->unsigned()->index()->after('status_id');
            $table->boolean('is_comment')->nullable()->index()->after('status_profile_id');
            $table->dropColumn('flagged');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->dropColumn('status_profile_id');
            $table->dropColumn('is_comment');
            $table->boolean('flagged')->default(false);
        });
    }
}
