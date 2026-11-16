<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('collections', 'sort')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->string('sort', 10)->default('oldest');
            });
        }

        if (! Schema::hasIndex('collections', ['profile_id'])) {
            Schema::table('collections', function (Blueprint $table) {
                $table->index('profile_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('collections', ['profile_id'])) {
            Schema::table('collections', function (Blueprint $table) {
                $table->dropIndex(['profile_id']);
            });
        }

        if (Schema::hasColumn('collections', 'sort')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->dropColumn('sort');
            });
        }
    }
};
