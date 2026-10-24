<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Small instances get their legacy direct messages converted right here.
     * Anything bigger is left to `php artisan dm:backfill-conversations`,
     * which is chunked, resumable and safe to run while the app is live.
     */
    public function up(): void
    {
        if (! Schema::hasTable('direct_messages')) {
            return;
        }

        $threshold = (int) config('dm.backfill.inline_threshold', 25000);
        $count = DB::table('direct_messages')->count();

        if ($count === 0) {
            return;
        }

        if ($count > $threshold) {
            echo PHP_EOL."  {$count} legacy direct messages found, skipping the inline backfill.".PHP_EOL;
            echo '  Run: php artisan dm:backfill-conversations'.PHP_EOL.PHP_EOL;

            return;
        }

        Artisan::call('dm:backfill-conversations', ['--force' => true]);
    }

    public function down(): void
    {
        //
    }
};
