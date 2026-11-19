<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('profiles', 'can_collect')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('can_collect', 20)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('profiles', 'can_collect')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('can_collect');
        });
    }
};
