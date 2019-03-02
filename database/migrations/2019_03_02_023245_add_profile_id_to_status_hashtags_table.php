<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProfileIdToStatusHashtagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('status_hashtags', function (Blueprint $table) {
            $table->bigInteger('profile_id')->unsigned()->nullable()->index()->after('hashtag_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('status_hashtags', function (Blueprint $table) {
            $table->dropColumn('profile_id');
        });
    }
}
