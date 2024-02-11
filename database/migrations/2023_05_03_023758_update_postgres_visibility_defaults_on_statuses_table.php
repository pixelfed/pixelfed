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
        $type = config('database.default');

        if ($type === 'pgsql') {
            DB::statement('ALTER TABLE statuses DROP CONSTRAINT IF EXISTS statuses_visibility_check');

            $types = ['public', 'unlisted', 'private', 'direct', 'draft'];
            $result = implode(', ', array_map(function ($value) {
                return sprintf("'%s'::character varying", $value);
            }, $types));

            DB::statement("ALTER TABLE statuses ADD CONSTRAINT statuses_visibility_check CHECK (visibility::text = ANY (ARRAY[$result]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
