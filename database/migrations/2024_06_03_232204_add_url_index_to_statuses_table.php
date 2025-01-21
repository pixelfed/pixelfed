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
        Schema::table('statuses', function (Blueprint $table) {
            $indexes = Schema::getIndexes('statuses');
            $indexesFound = collect($indexes)->map(function($i) { return $i['name']; })->toArray();
            if (!in_array('statuses_url_index', $indexesFound)) {
                $table->index('url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $indexes = Schema::getIndexes('statuses');
            $indexesFound = collect($indexes)->map(function($i) { return $i['name']; })->toArray();
            if (in_array('statuses_url_index', $indexesFound)) {
                $table->dropIndex('statuses_url_index');
            }
        });
    }
};
