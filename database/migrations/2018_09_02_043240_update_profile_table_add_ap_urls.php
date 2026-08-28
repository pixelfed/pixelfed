<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProfileTableAddApUrls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('inbox_url')->nullable()->after('sharedInbox');
            $table->string('outbox_url')->nullable()->after('inbox_url');
            $table->string('follower_url')->nullable()->after('outbox_url');
            $table->string('following_url')->nullable()->after('follower_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
