<?php

// GLITCH MODIFIED: upstream 0.14 added a migration with this same name, guarded by
// `config('database.default') !== 'postgres'`. The connection is called 'pgsql', so upstream's version
// never runs. Kept the fork's unguarded version (making these columns nullable is safe on every driver).

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
