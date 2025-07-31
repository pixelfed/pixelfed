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
        if (config('database.default') === 'pgsql')
            return;

        Schema::table('hashtags', function (Blueprint $table) {
            $table->string('name')->collation('utf8mb4_unicode_520_ci')->change();
            $table->string('slug')->collation('utf8mb4_unicode_520_ci')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'pgsql')
            return;

        Schema::table('hashtags', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('slug')->change();
        });
    }
};
