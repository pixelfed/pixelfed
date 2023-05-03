<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Hashtag;
use App\StatusHashtag;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $type = config('database.default');

        if($type !== 'pgsql') {
        	return;
        }

        foreach(Hashtag::lazyById(50, 'id') as $tag) {
        	$dups = Hashtag::where('name', 'ilike', $tag->name)->orderBy('id')->get();
        	if($dups->count() === 1) {
        		continue;
        	}

        	$first = $dups->shift();
        	$dups->each(function($dup) use($first) {
        		StatusHashtag::whereHashtagId($dup->id)->update(['hashtag_id' => $first->id]);
        		$dup->delete();
        	});
        }

        Cache::clear();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
