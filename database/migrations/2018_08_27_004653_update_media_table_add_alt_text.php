<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMediaTableAddAltText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('original_sha256')->nullable()->index()->after('user_id');
            $table->string('optimized_sha256')->nullable()->index()->after('original_sha256');
            $table->string('caption')->nullable()->after('thumbnail_url');
            $table->string('hls_path')->nullable()->after('caption');
            $table->timestamp('hls_transcoded_at')->nullable()->after('processed_at');
            $table->string('key')->nullable();
            $table->json('metadata')->nullable();
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
            $table->dropColumn(['original_sha256','optimized_sha256','caption','hls_path','hls_transcoded_at','key','metadata']);
        });
    }
}
