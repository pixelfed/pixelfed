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
            $table->boolean('has_roles')->default(false);
            $table->unsignedInteger('parent_id')->nullable();
            $table->tinyInteger('role_id')->unsigned()->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_roles');
            $table->dropColumn('parent_id');
            $table->dropColumn('role_id');
        });
    }
};
