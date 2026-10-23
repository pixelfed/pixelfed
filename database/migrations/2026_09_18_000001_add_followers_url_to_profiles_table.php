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
        if (Schema::hasColumn('profiles', 'followers_url')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('followers_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('profiles', 'followers_url')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('followers_url');
        });
    }
};
