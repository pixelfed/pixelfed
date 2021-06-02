<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFollowerTableAddRemoteFlags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('followers', function (Blueprint $table) {
            $table->boolean('local_profile')->default(true)->index()->after('following_id');
            $table->boolean('local_following')->default(true)->index()->after('local_profile');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('followers', function (Blueprint $table) {
            $table->dropColumn(['local_profile','local_following']);
        });
    }
}
