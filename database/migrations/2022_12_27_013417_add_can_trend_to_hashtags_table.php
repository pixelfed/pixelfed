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
        Schema::table('hashtags', function (Blueprint $table) {
            $table->unsignedInteger('cached_count')->nullable();
            $table->boolean('can_trend')->nullable()->index()->after('slug');
            $table->boolean('can_search')->nullable()->index()->after('can_trend');
            $table->index('is_nsfw');
            $table->index('is_banned');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hashtags', function (Blueprint $table) {
            $table->dropColumn('cached_count');
            $table->dropColumn('can_trend');
            $table->dropColumn('can_search');
            $table->dropIndex('hashtags_is_nsfw_index');
            $table->dropIndex('hashtags_is_banned_index');
        });
    }
};
