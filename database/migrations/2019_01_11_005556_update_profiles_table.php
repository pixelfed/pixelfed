<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('unlisted')->default(false)->index()->after('bio');
            $table->boolean('cw')->default(false)->index()->after('unlisted');
            $table->boolean('no_autolink')->default(false)->index()->after('cw');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['unlisted', 'cw', 'no_autolink']);
        });
    }
}
