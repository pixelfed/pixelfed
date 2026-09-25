<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove legacy 'App\Status'-aliased rows left dangling by status deletions
     * that ran before the cleanup jobs matched the legacy morph alias. Only
     * rows whose referenced status no longer exists are removed; legacy rows
     * pointing at a live status are still valid and are left untouched.
     */
    public function up(): void
    {
        $tables = [
            'reports' => ['object_type', 'object_id'],
            'account_interstitials' => ['item_type', 'item_id'],
            'collection_items' => ['object_type', 'object_id'],
        ];

        foreach ($tables as $table => [$typeCol, $idCol]) {
            $orphanIds = DB::table($table)
                ->leftJoin('statuses', 'statuses.id', '=', "$table.$idCol")
                ->where("$table.$typeCol", 'App\\Status')
                ->whereNull('statuses.id')
                ->pluck("$table.id");

            $orphanIds->chunk(1000)->each(function ($ids) use ($table) {
                DB::table($table)->whereIn('id', $ids->all())->delete();
            });
        }
    }

    public function down(): void
    {
        // Irreversible: orphaned rows referencing deleted statuses cannot be
        // reconstructed.
    }
};
