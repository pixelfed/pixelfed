<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_conversation_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('profile_id');
            $table->string('state', 16)->default('active');
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('muted_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'profile_id'], 'dm_cp_conversation_profile_unique');
            $table->index(['profile_id', 'state', 'last_activity_at'], 'dm_cp_inbox_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_conversation_participants');
    }
};
