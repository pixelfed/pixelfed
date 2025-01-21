<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avatars', function (Blueprint $table) {
            $indexes = Schema::getIndexes('avatars');
            $indexesFound = collect($indexes)->map(function($i) { return $i['name']; })->toArray();
            if (in_array('avatars_cdn_url_unique', $indexesFound)) {
                $table->dropUnique('avatars_cdn_url_unique');
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
        //
    }
};
