<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('group_posts', function (Blueprint $table) {
            if (DB::getDriverName() === 'sqlite') {
                $table->dropUnique(['status_id']);
            }
            $table->dropColumn('status_id');
            $table->dropColumn('reply_child_id');
            $table->dropColumn('in_reply_to_id');
            $table->dropColumn('reblog_of_id');
            $table->text('caption')->nullable();
            $table->string('visibility')->nullable();
            $table->boolean('is_nsfw')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->text('cw_summary')->nullable();
            $table->json('media_ids')->nullable();
            $table->boolean('comments_disabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_posts', function (Blueprint $table) {
            $table->bigInteger('status_id')->unsigned()->unique()->nullable();
            $table->bigInteger('reply_child_id')->unsigned()->nullable();
            $table->bigInteger('in_reply_to_id')->unsigned()->nullable();
            $table->bigInteger('reblog_of_id')->unsigned()->nullable();
            $table->dropColumn('caption');
            $table->dropColumn('is_nsfw');
            $table->dropColumn('visibility');
            $table->dropColumn('likes_count');
            $table->dropColumn('cw_summary');
            $table->dropColumn('media_ids');
            $table->dropColumn('comments_disabled');
        });
    }
};
