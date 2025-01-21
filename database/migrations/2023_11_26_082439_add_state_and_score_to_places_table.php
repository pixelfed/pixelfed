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
        Schema::table('places', function (Blueprint $table) {
            $table->string('state')->nullable()->index()->after('name');
            $table->tinyInteger('score')->default(0)->index()->after('long');
            $table->unsignedBigInteger('cached_post_count')->nullable();
            $table->timestamp('last_checked_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn('state');
            $table->dropColumn('score');
            $table->dropColumn('cached_post_count');
            $table->dropColumn('last_checked_at');
        });
    }
};
