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
        Schema::table('users', function (Blueprint $table) {
            $table->string('expo_token')->nullable();
            $table->boolean('notify_like')->default(true);
            $table->boolean('notify_follow')->default(true);
            $table->boolean('notify_mention')->default(true);
            $table->boolean('notify_comment')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('expo_token');
            $table->dropColumn('notify_like');
            $table->dropColumn('notify_follow');
            $table->dropColumn('notify_mention');
            $table->dropColumn('notify_comment');
        });
    }
};
