<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
           $table->index('followers_count', 'profiles_followers_count_index');
           $table->index('following_count', 'profiles_following_count_index');
           $table->index('status_count', 'profiles_status_count_index');
           $table->index('is_private', 'profiles_is_private_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_followers_count_index');
            $table->dropIndex('profiles_following_count_index');
            $table->dropIndex('profiles_status_count_index');
            $table->dropIndex('profiles_is_private_index');
        });
    }
};
