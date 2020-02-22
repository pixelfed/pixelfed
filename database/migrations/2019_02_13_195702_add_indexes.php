<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->index('visibility', 'statuses_visibility_index');
            $table->index(['in_reply_to_id', 'reblog_of_id'], 'statuses_in_reply_or_reblog_index');
            $table->index('uri', 'statuses_uri_index');
            $table->index('is_nsfw', 'statuses_is_nsfw_index');
            $table->index('created_at', 'statuses_created_at_index');
            $table->index('profile_id', 'statuses_profile_id_index');
            $table->index('local', 'statuses_local_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('created_at', 'notifications_created_at_index');
            $table->index('actor_id', 'notifications_actor_id_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->index('domain', 'profiles_domain_index');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->index('user_id', 'media_user_id_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->index('created_at', 'likes_created_at_index');
        });

        Schema::table('followers', function (Blueprint $table) {
            $table->index('created_at', 'followers_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->dropIndex('statuses_visibility_index');
            $table->dropIndex('statuses_in_reply_or_reblog_index');
            $table->dropIndex('statuses_uri_index');
            $table->dropIndex('statuses_is_nsfw_index');
            $table->dropIndex('statuses_created_at_index');
            $table->dropIndex('statuses_profile_id_index');
            $table->dropIndex('statuses_local_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_created_at_index');
            $table->dropIndex('notifications_actor_id_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_domain_index');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('media_user_id_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex('likes_created_at_index');
        });

        Schema::table('followers', function (Blueprint $table) {
            $table->dropIndex('followers_created_at_index');
        });
    }
}
