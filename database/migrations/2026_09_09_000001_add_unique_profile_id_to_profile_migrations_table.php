<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any pre-existing duplicate rows (a symptom of the race this
        // constraint prevents), keeping the most recent migration per profile,
        // so the unique index can be added cleanly.
        $dupes = DB::table('profile_migrations')
            ->select('profile_id')
            ->groupBy('profile_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('profile_id');

        foreach ($dupes as $profileId) {
            $keepId = DB::table('profile_migrations')
                ->where('profile_id', $profileId)
                ->orderByDesc('id')
                ->value('id');

            DB::table('profile_migrations')
                ->where('profile_id', $profileId)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('profile_migrations', function (Blueprint $table) {
            $table->unique('profile_id', 'profile_migrations_profile_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('profile_migrations', function (Blueprint $table) {
            $table->dropUnique('profile_migrations_profile_id_unique');
        });
    }
};
