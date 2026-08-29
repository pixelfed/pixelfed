<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The collation that correctly differentiates characters outside the
     * Basic Multilingual Plane (codepoints >= 0x10000). The historic default
     * of utf8mb4_unicode_ci treats all such characters as equal, which causes
     * distinct hashtags (e.g. Shavian vs cuneiform of the same length) to
     * collide on the unique name/slug indexes.
     */
    private const TARGET_COLLATION = 'utf8mb4_unicode_520_ci';

    /**
     * The collation to restore on rollback (the previous project default).
     */
    private const PREVIOUS_COLLATION = 'utf8mb4_unicode_ci';

    /**
     * Column definitions to keep intact while altering the collation. Both are
     * VARCHAR(255) NOT NULL with unique indexes; MODIFY preserves the index.
     */
    private const COLUMNS = ['name', 'slug'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->setCollation(self::TARGET_COLLATION);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->setCollation(self::PREVIOUS_COLLATION);
    }

    /**
     * Apply the given collation to the hashtags name and slug columns.
     *
     * Laravel's fluent ->change() does not reliably emit a collation-only
     * change on MySQL/MariaDB, so issue an explicit MODIFY per column.
     */
    private function setCollation(string $collation): void
    {
        if (! $this->isMysql()) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            DB::statement(
                'ALTER TABLE `hashtags` MODIFY `'.$column.'` '.
                'VARCHAR(255) CHARACTER SET utf8mb4 COLLATE '.$collation.' NOT NULL'
            );
        }
    }

    /**
     * This migration only applies to MySQL/MariaDB. Postgres compares
     * hashtags with ILIKE (no collation quirk) and other drivers (e.g. the
     * sqlite test database) do not support these collations.
     *
     * Note: Laravel 11+ reports MariaDB as the distinct "mariadb" driver, so
     * both must be matched here.
     */
    private function isMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
