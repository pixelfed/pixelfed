<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            if (! Schema::hasColumn('instances', 'block_sync_url')) {
                $table->string('block_sync_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            if (Schema::hasColumn('instances', 'block_sync_url')) {
                $table->dropColumn('block_sync_url');
            }
        });
    }
};
