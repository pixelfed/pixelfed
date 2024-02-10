<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountStatusToProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop old columns, fix stories
        if(Schema::hasColumn('profiles', 'hub_url')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->dropColumn(['verify_token','secret','salmon_url','hub_url']);
            });
        }

        if(Schema::hasColumn('stories', 'bigIncrements')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->dropColumn('bigIncrements');
            });
            Schema::table('stories', function (Blueprint $table) {
                $table->bigIncrements('id')->first();
            });
        }

        // Add account status to profile and user tables

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('status')->nullable()->index()->after('username');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->nullable()->index()->after('email');
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
            $table->string('verify_token')->nullable();
            $table->string('secret')->nullable();
            $table->string('salmon_url')->nullable();
            $table->string('hub_url')->nullable();
        });

        if (Schema::hasTable('stories')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->dropColumn('id');
            });
            Schema::table('stories', function (Blueprint $table) {
                $table->bigIncrements('bigIncrements')->first();
            });
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
