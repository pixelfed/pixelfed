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
        Schema::create('user_app_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->unique()->index();
            $table->bigInteger('profile_id')->unsigned()->unique()->index();
            $table->json('common')->nullable();
            $table->json('custom')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_app_settings');
    }
};
