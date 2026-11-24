<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            if (! Schema::hasColumn('instances', 'delivery_down_since')) {
                $table->timestamp('delivery_down_since')->nullable();
            }

            if (! Schema::hasColumn('instances', 'delivery_failed_at')) {
                $table->timestamp('delivery_failed_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            if (Schema::hasColumn('instances', 'delivery_failed_at')) {
                $table->dropIndex(['delivery_failed_at']);
                $table->dropColumn('delivery_failed_at');
            }

            if (Schema::hasColumn('instances', 'delivery_down_since')) {
                $table->dropColumn('delivery_down_since');
            }
        });
    }
};
