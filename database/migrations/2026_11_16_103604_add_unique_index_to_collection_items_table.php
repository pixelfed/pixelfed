<?php

use App\Services\CollectionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('collection_items', ['collection_id', 'object_id'], 'unique')) {
            return;
        }

        $duplicates = DB::table('collection_items')
            ->select('collection_id', 'object_id')
            ->groupBy('collection_id', 'object_id')
            ->havingRaw('count(*) > 1')
            ->get();

        $touched = [];

        foreach ($duplicates as $duplicate) {
            $keep = DB::table('collection_items')
                ->where('collection_id', $duplicate->collection_id)
                ->where('object_id', $duplicate->object_id)
                ->orderBy('id')
                ->value('id');

            DB::table('collection_items')
                ->where('collection_id', $duplicate->collection_id)
                ->where('object_id', $duplicate->object_id)
                ->where('id', '!=', $keep)
                ->delete();

            $touched[$duplicate->collection_id] = true;
        }

        DB::table('collection_items')
            ->whereNull('order')
            ->distinct()
            ->pluck('collection_id')
            ->each(function ($collectionId) use (&$touched) {
                $touched[$collectionId] = true;
            });

        foreach (array_keys($touched) as $collectionId) {
            DB::table('collection_items')
                ->where('collection_id', $collectionId)
                ->orderBy('order')
                ->orderBy('id')
                ->pluck('id')
                ->each(function ($id, $index) {
                    DB::table('collection_items')->where('id', $id)->update(['order' => $index]);
                });

            rescue(fn () => CollectionService::deleteCollection($collectionId), report: false);
        }

        Schema::table('collection_items', function (Blueprint $table) {
            $table->unique(['collection_id', 'object_id']);
        });

        if (! Schema::hasIndex('collection_items', ['collection_id', 'order'])) {
            Schema::table('collection_items', function (Blueprint $table) {
                $table->index(['collection_id', 'order']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('collection_items', ['collection_id', 'order'])) {
            Schema::table('collection_items', function (Blueprint $table) {
                $table->dropIndex(['collection_id', 'order']);
            });
        }

        if (Schema::hasIndex('collection_items', ['collection_id', 'object_id'], 'unique')) {
            Schema::table('collection_items', function (Blueprint $table) {
                $table->dropUnique(['collection_id', 'object_id']);
            });
        }
    }
};
