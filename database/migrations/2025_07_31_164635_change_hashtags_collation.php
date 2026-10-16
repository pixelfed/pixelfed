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
     * Tables that reference hashtags.id via a hashtag_id column. Rows in these
     * tables must be repointed from a duplicate hashtag to the surviving one
     * before the duplicate is deleted, so no references are orphaned.
     *
     * group_post_hashtags is intentionally excluded: it references the
     * separate group_hashtags table, not hashtags.
     */
    private const REFERENCING_TABLES = [
        'status_hashtags',
        'hashtag_follows',
        'hashtag_related',
        'discover_category_hashtags',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->isMysql()) {
            return;
        }

        // The unique indexes on name/slug are enforced during the ALTER. Under
        // the stricter target collation, rows that were previously distinct may
        // now be considered equal, so merge those duplicates first to avoid a
        // "Duplicate entry" (1062) failure on the ALTER TABLE.
        $this->mergeDuplicates('name');
        $this->mergeDuplicates('slug');

        $this->setCollation(self::TARGET_COLLATION);
    }

    /**
     * Reverse the migrations.
     *
     * Note: merged duplicate rows are not restored on rollback (the data is
     * gone). Only the collation is reverted.
     */
    public function down(): void
    {
        if (! $this->isMysql()) {
            return;
        }

        $this->setCollation(self::PREVIOUS_COLLATION);
    }

    /**
     * Merge hashtags that collide on the given column under the target
     * collation. For each group of colliding rows, the lowest id is kept and
     * all references are repointed to it before the losing rows are deleted.
     */
    private function mergeDuplicates(string $column): void
    {
        $collatedColumn = 'CONVERT(`'.$column.'` USING utf8mb4) COLLATE '.self::TARGET_COLLATION;

        // Group by the value compared under the target collation. Any group
        // with more than one row would violate the unique index after the
        // collation change.
        $groups = DB::table('hashtags')
            ->select(DB::raw('MIN(id) as keep_id'))
            ->groupBy(DB::raw($collatedColumn))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $keepId = (int) $group->keep_id;

            // Find the losing rows: everything in the same collated group
            // except the surviving (lowest) id.
            $keepValue = DB::table('hashtags')->where('id', $keepId)->value($column);

            $losers = DB::table('hashtags')
                ->whereRaw($collatedColumn.' = CONVERT(? USING utf8mb4) COLLATE '.self::TARGET_COLLATION, [$keepValue])
                ->where('id', '!=', $keepId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (empty($losers)) {
                continue;
            }

            $this->repointReferences($losers, $keepId);

            DB::table('hashtags')->whereIn('id', $losers)->delete();
        }
    }

    /**
     * Repoint references from the losing hashtag ids to the surviving id.
     * UPDATE IGNORE avoids aborting on unique constraints in the referencing
     * tables; any rows that could not be repointed (because an equivalent
     * reference to keep_id already exists) are then deleted.
     */
    private function repointReferences(array $loserIds, int $keepId): void
    {
        $placeholders = implode(',', array_fill(0, count($loserIds), '?'));

        foreach (self::REFERENCING_TABLES as $table) {
            if (! DB::getSchemaBuilder()->hasColumn($table, 'hashtag_id')) {
                continue;
            }

            DB::statement(
                'UPDATE IGNORE `'.$table.'` SET `hashtag_id` = ? WHERE `hashtag_id` IN ('.$placeholders.')',
                array_merge([$keepId], $loserIds)
            );

            DB::statement(
                'DELETE FROM `'.$table.'` WHERE `hashtag_id` IN ('.$placeholders.')',
                $loserIds
            );
        }
    }

    /**
     * Apply the given collation to the hashtags name and slug columns.
     *
     * Laravel's fluent ->change() does not reliably emit a collation-only
     * change on MySQL/MariaDB, so issue an explicit MODIFY per column.
     */
    private function setCollation(string $collation): void
    {
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
