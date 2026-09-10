<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add a covering index for per-user storage aggregation.
     *
     * `SUM(size) WHERE user_id = ?` (UserStorageService::calculateStorageUsed)
     * previously required a full table scan because media.user_id was not
     * indexed. The composite (user_id, size) lets the aggregate be served
     * entirely from the index. Uses INPLACE/LOCK=NONE so it does not block
     * writes on large instances.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('
            ALTER TABLE `media`
            ADD INDEX `media_user_id_size_index` (`user_id`, `size`),
            ALGORITHM=INPLACE,
            LOCK=NONE
        ');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('
            ALTER TABLE `media`
            DROP INDEX `media_user_id_size_index`,
            ALGORITHM=INPLACE,
            LOCK=NONE
        ');
    }
};
