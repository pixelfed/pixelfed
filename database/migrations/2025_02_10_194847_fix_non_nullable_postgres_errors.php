<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 2018_08_12_042648_update_status_table_change_caption_to_text calls
     * `->change()` without `->nullable()`, making both columns NOT NULL.
     * DirectMessageController still writes null into caption, which errors
     * on PostgreSQL.
     */
    public function up(): void
    {
        Schema::table('statuses', function ($table) {
            if (config('database.default') !== 'postgres') {
                return;
            }

            $table->text('caption')->nullable()->change();
            $table->text('rendered')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
