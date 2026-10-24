<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            if (! Schema::hasColumn('instances', 'delivery_timeout')) {
                $table->boolean('delivery_timeout')->default(false)->index();
            }

            if (! Schema::hasColumn('instances', 'delivery_next_after')) {
                $table->timestamp('delivery_next_after')->nullable();
            }

            if (! Schema::hasColumn('instances', 'delivery_failures')) {
                $table->unsignedSmallInteger('delivery_failures')->default(0)->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            if (Schema::hasColumn('instances', 'delivery_failures')) {
                $table->dropColumn('delivery_failures');
            }
        });
    }
};
