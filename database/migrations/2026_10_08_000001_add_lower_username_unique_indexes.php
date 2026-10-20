<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Functional unique indexes on lower(username) enforce case-insensitive
     * uniqueness at the database level. Only PostgreSQL supports this syntax,
     * so it is guarded and skipped on other drivers.
     *
     * Operational caveat: on a database that already holds case-duplicate
     * usernames (e.g. `alice` and `Alice`) the CREATE UNIQUE INDEX will fail.
     * Those duplicates must be resolved first; this migration intentionally
     * does not remove rows.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_username_lower_unique ON users (lower(username))');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS profiles_username_lower_unique ON profiles (lower(username))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS users_username_lower_unique');
        DB::statement('DROP INDEX IF EXISTS profiles_username_lower_unique');
    }
};
