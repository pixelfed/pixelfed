<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('profile_id')->index();
            $table->string('type', 32)->default('text');
            $table->text('body')->nullable();
            $table->json('entities')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->string('ap_object_uri', 1024)->nullable();
            $table->char('ap_object_hash', 64)->nullable()->unique();
            $table->unsignedBigInteger('in_reply_to_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable()->index();
            $table->unsignedBigInteger('legacy_dm_id')->nullable()->unique();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'id'], 'dm_messages_conversation_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_messages');
    }
};
