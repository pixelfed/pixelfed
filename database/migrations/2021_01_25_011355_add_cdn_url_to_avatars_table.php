<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCdnUrlToAvatarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->string('cdn_url')->unique()->index()->nullable()->after('remote_url');
            $table->unsignedInteger('size')->nullable()->after('cdn_url');
            $table->boolean('is_remote')->nullable()->index()->after('cdn_url');
            $table->dropColumn('thumb_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->dropColumn('cdn_url');
            $table->dropColumn('size');
            $table->dropColumn('is_remote');
            $table->string('thumb_path')->nullable();
        });
    }
}
