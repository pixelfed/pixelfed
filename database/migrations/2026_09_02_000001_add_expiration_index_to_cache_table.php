<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->index('expiration');
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->index('expiration');
        });
    }

    public function down()
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->dropIndex(['expiration']);
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->dropIndex(['expiration']);
        });
    }
};
