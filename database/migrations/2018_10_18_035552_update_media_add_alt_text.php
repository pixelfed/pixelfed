<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMediaAddAltText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('license')->nullable()->after('filter_class');
            $table->boolean('is_nsfw')->default(false)->after('user_id');
            $table->tinyInteger('version')->default(1);
            $table->boolean('remote_media')->default(false)->after('is_nsfw');
            $table->string('remote_url')->nullable()->after('thumbnail_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['license', 'is_nsfw', 'version', 'remote_media', 'remote_url']);
        });
    }
}
