<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusVisibilityToStatusHashtagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('status_hashtags', function (Blueprint $table) {
            $table->string('status_visibility')->nullable()->index()->after('profile_id');
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
            $table->dropColumn('status_visibility');
        });
    }
}
