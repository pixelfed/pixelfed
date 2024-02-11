<?php

use App\Avatar;
use App\Services\AccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

class FixCdnUrlInAvatarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $baseUrl = 'https://'.config('pixelfed.domain.app');
        Avatar::whereNotNull('cdn_url')
            ->chunk(50, function ($avatars) use ($baseUrl) {
                foreach ($avatars as $avatar) {
                    if (substr($avatar->cdn_url, 0, 23) === '/storage/cache/avatars/') {
                        $avatar->cdn_url = $baseUrl.$avatar->cdn_url;
                        $avatar->save();
                    }
                    Cache::forget('avatar:'.$avatar->profile_id);
                    AccountService::del($avatar->profile_id);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
