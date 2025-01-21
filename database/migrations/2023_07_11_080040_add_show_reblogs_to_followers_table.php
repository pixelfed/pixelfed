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
        Schema::table('followers', function (Blueprint $table) {
            $table->boolean('show_reblogs')->default(true)->index()->after('local_following');
            $table->boolean('notify')->default(false)->index()->after('show_reblogs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followers', function (Blueprint $table) {
            $table->dropColumn('show_reblogs');
            $table->dropColumn('notify');
        });
    }
};
