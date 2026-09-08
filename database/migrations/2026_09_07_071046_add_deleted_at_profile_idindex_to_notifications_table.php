<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('
            ALTER TABLE `notifications`
            ADD INDEX `notifications_profile_deleted_id_index`
                (`profile_id`, `deleted_at`, `id`),
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
            ALTER TABLE `notifications`
            DROP INDEX `notifications_profile_deleted_id_index`,
            ALGORITHM=INPLACE,
            LOCK=NONE
        ');
    }
};
