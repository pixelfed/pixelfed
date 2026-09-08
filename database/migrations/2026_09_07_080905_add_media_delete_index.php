<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('
            ALTER TABLE `media`
            ADD INDEX `media_unoptimized_recent_index`
                (`processed_at`, `remote_url`, `deleted_at`, `created_at`, `id`),
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
            DROP INDEX `media_unoptimized_recent_index`,
            ALGORITHM=INPLACE,
            LOCK=NONE
        ');
    }
};
