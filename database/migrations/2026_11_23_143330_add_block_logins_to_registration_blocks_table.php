<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_blocks', function (Blueprint $table) {
            $table->boolean('block_logins')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('registration_blocks', function (Blueprint $table) {
            $table->dropColumn('block_logins');
        });
    }
};
