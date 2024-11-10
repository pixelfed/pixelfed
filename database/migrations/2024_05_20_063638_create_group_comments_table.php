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
        Schema::create('group_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id')->index();
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable()->index();
            $table->unsignedBigInteger('in_reply_to_id')->nullable()->index();
            $table->string('remote_url')->nullable()->unique()->index();
            $table->text('caption')->nullable();
            $table->boolean('is_nsfw')->default(false);
            $table->string('visibility')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->text('cw_summary')->nullable();
            $table->json('media_ids')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->default('text')->nullable();
            $table->boolean('local')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_comments');
    }
};
