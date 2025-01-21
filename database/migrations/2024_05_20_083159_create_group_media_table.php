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
        Schema::create('group_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('profile_id');
            $table->unsignedBigInteger('status_id')->nullable()->index();
            $table->string('media_path')->unique();
            $table->text('thumbnail_url')->nullable();
            $table->text('cdn_url')->nullable();
            $table->text('url')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->text('cw_summary')->nullable();
            $table->string('license')->nullable();
            $table->string('blurhash')->nullable();
            $table->tinyInteger('order')->unsigned()->default(1);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('local_user')->default(true);
            $table->boolean('is_cached')->default(false);
            $table->boolean('is_comment')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->string('version')->default(1);
            $table->boolean('skip_optimize')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('thumbnail_generated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_media');
    }
};
