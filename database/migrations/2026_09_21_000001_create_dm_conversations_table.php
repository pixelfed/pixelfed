<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('type', 16)->default('dm')->index();
            $table->char('participants_hash', 64)->unique();
            $table->string('name', 100)->nullable();
            $table->string('context_uri', 1024)->nullable();
            $table->string('conversation_uri', 1024)->nullable();
            $table->unsignedBigInteger('created_by_profile_id')->nullable()->index();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_conversations');
    }
};
