<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('UPDATE statuses SET reply_count = 0 WHERE reply_count IS NULL');

        Schema::table('statuses', function (Blueprint $table) {
            $table->unsignedInteger('reply_count')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->unsignedInteger('reply_count')->nullable()->default(null)->change();
        });
    }
};
