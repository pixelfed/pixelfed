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
        Schema::table('instances', function (Blueprint $table) {
            $table->boolean('active_deliver')->nullable()->index()->after('domain');
            $table->boolean('valid_nodeinfo')->nullable();
            $table->timestamp('nodeinfo_last_fetched')->nullable();
            $table->boolean('delivery_timeout')->default(false);
            $table->timestamp('delivery_next_after')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropColumn('active_deliver');
            $table->dropColumn('valid_nodeinfo');
            $table->dropColumn('nodeinfo_last_fetched');
            $table->dropColumn('delivery_timeout');
            $table->dropColumn('delivery_next_after');
        });
    }
};
