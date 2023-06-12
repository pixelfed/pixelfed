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
        Schema::create('import_posts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('profile_id')->unsigned()->index();
            $table->unsignedInteger('user_id')->index();
            $table->string('service')->index();
            $table->string('post_hash')->nullable()->index();
            $table->string('filename')->index();
            $table->tinyInteger('media_count')->unsigned();
            $table->string('post_type')->nullable();
            $table->text('caption')->nullable();
            $table->json('media')->nullable();
            $table->tinyInteger('creation_year')->unsigned()->nullable();
            $table->tinyInteger('creation_month')->unsigned()->nullable();
            $table->tinyInteger('creation_day')->unsigned()->nullable();
            $table->tinyInteger('creation_id')->unsigned()->nullable();
            $table->bigInteger('status_id')->unsigned()->nullable()->unique()->index();
            $table->timestamp('creation_date')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('skip_missing_media')->default(false)->index();
            $table->unique(['user_id', 'post_hash']);
            $table->unique(['user_id', 'creation_year', 'creation_month', 'creation_day', 'creation_id'], 'import_posts_uid_phash_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_posts');
    }
};
