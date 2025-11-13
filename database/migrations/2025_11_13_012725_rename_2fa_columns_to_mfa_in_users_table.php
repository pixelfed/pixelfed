<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('2fa_enabled', 'mfa_enabled');
            $table->renameColumn('2fa_secret', 'mfa_secret');
            $table->renameColumn('2fa_backup_codes', 'mfa_backup_codes');
            $table->renameColumn('2fa_setup_at', 'mfa_setup_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('mfa_enabled', '2fa_enabled');
            $table->renameColumn('mfa_secret', '2fa_secret');
            $table->renameColumn('mfa_backup_codes', '2fa_backup_codes');
            $table->renameColumn('mfa_setup_at', '2fa_setup_at');
        });
    }
};
