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
        Schema::create('group_likes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('profile_id')->index();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->boolean('local')->default(true);
            $table->unique(['group_id', 'profile_id', 'status_id', 'comment_id'], 'group_likes_gpsc_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_likes');
    }
};
