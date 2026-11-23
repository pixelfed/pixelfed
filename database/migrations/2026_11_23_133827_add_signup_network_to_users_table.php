<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('signup_country', 2)->nullable();
            $table->unsignedBigInteger('signup_asn')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('signup_country');
            $table->index('signup_asn');
            $table->index('app_register_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['signup_country']);
            $table->dropIndex(['signup_asn']);
            $table->dropIndex(['app_register_ip']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signup_country', 'signup_asn']);
        });
    }
};
