<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Add2faToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('2fa_enabled')->default(false);
            $table->string('2fa_secret')->nullable();
            $table->json('2fa_backup_codes')->nullable();
            $table->timestamp('2fa_setup_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['2fa_enabled','2fa_secret','2fa_backup_codes','2fa_setup_at']);
        });
    }
}
