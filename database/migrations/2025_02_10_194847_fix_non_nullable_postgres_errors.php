<?php

// GLITCH MODIFIED: fork-only migration, statuses.caption/rendered nullable (fixes Postgres not-null errors)

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
        Schema::table('statuses', function ($table) {
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
