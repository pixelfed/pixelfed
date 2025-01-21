<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Services\ConfigCacheService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $count = DB::table('statuses')->whereNull(['url', 'deleted_at'])->count();
        $res = [
            'count' => $count
        ];
        Storage::put('total_local_posts.json', json_encode($res, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
        ConfigCacheService::put('instance.stats.total_local_posts', $res['count']);
        Cache::forget('api:nodeinfo');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
