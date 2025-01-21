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
            $indexes = Schema::getIndexes('instances');
            $indexesFound = collect($indexes)->map(function($i) { return $i['name']; })->toArray();
            if (!in_array('instances_nodeinfo_last_fetched_index', $indexesFound)) {
                $table->index('nodeinfo_last_fetched');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $indexes = Schema::getIndexes('instances');
            $indexesFound = collect($indexes)->map(function($i) { return $i['name']; })->toArray();
            if (in_array('instances_nodeinfo_last_fetched_index', $indexesFound)) {
                $table->dropIndex('instances_nodeinfo_last_fetched_index');
            }
        });
    }
};
