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
        Schema::table('app_registers', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_attempts')->default(0)->after('uses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_registers', function (Blueprint $table) {
            $table->dropColumn('failed_attempts');
        });
    }
};
