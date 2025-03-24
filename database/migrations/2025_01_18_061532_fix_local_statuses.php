<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('statuses')
            ->join('profiles', 'profiles.id', '=', 'statuses.profile_id')
            ->leftJoin('users', 'users.id', '=', 'profiles.user_id')
            ->where('statuses.local', true)
            ->where('statuses.type', 'share')
            ->whereNull('users.id')
            ->update(['statuses.local' => false]);
    }

    public function down(): void
    {
        // No down migration needed since this is a data fix
    }
};
