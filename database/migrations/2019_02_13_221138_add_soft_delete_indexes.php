<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoftDeleteIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->index('deleted_at','avatars_deleted_at_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->index('deleted_at','profiles_deleted_at_index');
        });

        Schema::table('mentions', function (Blueprint $table) {
            $table->index('deleted_at','mentions_deleted_at_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->index('deleted_at','likes_deleted_at_index');
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->index('deleted_at','statuses_deleted_at_index');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->index('deleted_at','media_deleted_at_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('deleted_at','notifications_deleted_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('deleted_at','users_deleted_at_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->dropIndex('avatars_deleted_at_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_deleted_at_index');
        });

        Schema::table('mentions', function (Blueprint $table) {
            $table->dropIndex('mentions_deleted_at_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex('likes_deleted_at_index');
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->dropIndex('statuses_deleted_at_index');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('media_deleted_at_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_deleted_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_deleted_at_index');
        });
    }
}
