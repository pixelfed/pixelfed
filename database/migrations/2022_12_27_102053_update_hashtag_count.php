<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Hashtag;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Hashtag::withoutEvents(function() {
            Hashtag::whereNotNull('cached_count')->chunkById(50, function($hashtags) {
                foreach($hashtags as $hashtag) {
                    $count = DB::table('status_hashtags')->whereHashtagId($hashtag->id)->count();
                    $hashtag->cached_count = $count;
                    $hashtag->can_trend = true;
                    $hashtag->can_search = true;
                    $hashtag->save();
                }
            }, 'id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
