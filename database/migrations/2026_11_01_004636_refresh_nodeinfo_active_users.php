<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('users')->exists()) {
            return;
        }

        try {
            Artisan::call('app:instance-update-active-users');
        } catch (Throwable $e) {
            echo PHP_EOL.'  Could not refresh active user counts: '.$e->getMessage().PHP_EOL;
            echo '  Run: php artisan app:instance-update-active-users'.PHP_EOL.PHP_EOL;
        }
    }

    public function down(): void
    {
        // nothing to reverse ;)
    }
};
