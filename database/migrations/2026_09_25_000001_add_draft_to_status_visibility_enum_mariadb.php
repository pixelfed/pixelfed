<?php

use App\Util\Database\DatabaseDriver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'draft' to the statuses.visibility ENUM on MariaDB.
     *
     * The 2018 migration that widened this ENUM only ran its ALTER for the
     * literal 'mysql' driver, so fresh MariaDB installs (Laravel 11's dedicated
     * 'mariadb' driver) kept the original four-value ENUM and reject draft
     * statuses (error 1265 under strict mode; silent '' corruption otherwise).
     *
     * This companion migration repairs MariaDB only, matching how pgsql was
     * repaired by its own 2023 migration:
     *   - MySQL already has 'draft' (2018 migration).
     *   - pgsql's CHECK constraint already includes 'draft'
     *     (2023_05_03_..._update_postgres_visibility_defaults).
     *   - SQLite stores visibility as an unconstrained string.
     * So MariaDB is the only driver still missing the value.
     */
    public function up(): void
    {
        if (! DatabaseDriver::isMariadb()) {
            return;
        }

        if ($this->hasDraftVisibility()) {
            return;
        }

        DB::statement("ALTER TABLE statuses CHANGE COLUMN visibility visibility ENUM('public','unlisted','private','direct','draft') NOT NULL DEFAULT 'public'");
    }

    /**
     * Reverse the migrations.
     *
     * No-op: narrowing the ENUM would truncate any existing draft rows, and the
     * widened column already matches MySQL once 'draft' is present.
     */
    public function down(): void
    {
        //
    }

    /**
     * True when the visibility ENUM already lists 'draft'.
     */
    private function hasDraftVisibility(): bool
    {
        $column = DB::selectOne(
            'SHOW COLUMNS FROM statuses WHERE Field = ?',
            ['visibility']
        );

        return $column
            && isset($column->Type)
            && str_contains(strtolower($column->Type), "'draft'");
    }
};
