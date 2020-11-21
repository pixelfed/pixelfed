<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeleteAfterToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->timestamp('delete_after')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('delete_after')->nullable();
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
            $table->dropColumn('delete_after');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('delete_after');
        });
    }
}
